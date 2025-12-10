<?php
// pages/timeline.php
session_start();
require_once '../config/database.php';
$pageTitle = "Neighborly - Opportunities Timeline";
$authPage  = false;
$placeholderImage = "../static/img/placeholder.jpeg";
$signup_message = "";
$signup_error = "";
$locationsPath = __DIR__ . '/../config/locations.php';
if (!file_exists($locationsPath)) {
    die("Error: Locations file not found at: " . $locationsPath);
}
$locations = require $locationsPath;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organizer') {
        $signup_error = "Only organizers can create events.";
    } else {
        $title = trim($_POST['event_title'] ?? '');
        $description = trim($_POST['event_description'] ?? '');
        $location = trim($_POST['event_location'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $slots_available = (int)($_POST['slots_available'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? '');
        
        if (empty($title) || empty($description) || empty($location) || empty($start_date) || empty($end_date)) {
            $signup_error = "Please fill in all required fields.";
        } elseif ($slots_available <= 0) {
            $signup_error = "Slots available must be greater than 0.";
        } elseif (!in_array($location, $locations)) {
            $signup_error = "Please select a valid location.";
        } else {
            $start_datetime = strtotime($start_date);
            $end_datetime = strtotime($end_date);
            $now = time();
            
            if ($start_datetime === false || $end_datetime === false) {
                $signup_error = "Invalid date format.";
            } elseif ($start_datetime < $now) {
                $signup_error = "Start date and time cannot be in the past.";
            } elseif ($end_datetime < $now) {
                $signup_error = "End date and time cannot be in the past.";
            } elseif ($end_datetime <= $start_datetime) {
                $signup_error = "End date must be after start date.";
            } else {
                try {
                    $pdo = connect_to_database();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO events (
                            organizer_id, 
                            title, 
                            description, 
                            image_url, 
                            location, 
                            start_date, 
                            end_date, 
                            slots_available, 
                            is_approved, 
                            status, 
                            created_at, 
                            updated_at
                        ) VALUES (
                            :organizer_id,
                            :title,
                            :description,
                            :image_url,
                            :location,
                            :start_date,
                            :end_date,
                            :slots_available,
                            0,
                            'active',
                            NOW(),
                            NOW()
                        )
                    ");
                    
                    $stmt->execute([
                        'organizer_id' => $_SESSION['user_id'],
                        'title' => $title,
                        'description' => $description,
                        'image_url' => !empty($image_url) ? $image_url : null,
                        'location' => $location,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'slots_available' => $slots_available
                    ]);
                    
                    $signup_message = "Event created successfully! Your event is now under review by the administrator and will appear on the timeline once approved.";
                    
                } catch (PDOException $e) {
                    error_log("Event creation error: " . $e->getMessage());
                    $signup_error = "Error creating event. Please try again.";
                }
            }
        }
    }
}
if (isset($_POST['ajax_signup_event'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => ''];
    $event_id = (int)$_POST['ajax_signup_event'];
    
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = "Please log in to volunteer for events.";
    } elseif ($_SESSION['role'] !== 'volunteer') {
        $response['message'] = "Only volunteers can sign up for events.";
    } elseif ($event_id <= 0) {
        $response['message'] = "Invalid event ID.";
    } else {
        $volunteer_id = $_SESSION['user_id'];
        
        try {
            $pdo = connect_to_database();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $event_check = $pdo->prepare("SELECT event_id, slots_available FROM events WHERE event_id = :event_id AND status = 'active'");
            $event_check->execute(['event_id' => $event_id]);
            $event = $event_check->fetch();
            
            if (!$event) {
                $response['message'] = "Event not found or not active.";
            } else {
                $attendee_stmt = $pdo->prepare("
                    SELECT COUNT(*) as registered_count 
                    FROM event_attendees 
                    WHERE event_id = :event_id AND (status = 'registered' OR status = 'confirmed')
                ");
                $attendee_stmt->execute(['event_id' => $event_id]);
                $registered_count = $attendee_stmt->fetch()['registered_count'];
                
                $spots_remaining = $event['slots_available'] - $registered_count;
                
                if ($spots_remaining <= 0) {
                    $response['message'] = "No spots available for this event.";
                } else {
                    $already_signed = $pdo->prepare("
                        SELECT 1 FROM event_attendees 
                        WHERE event_id = :event_id AND volunteer_id = :volunteer_id AND (status = 'registered' OR status = 'confirmed')
                    ");
                    $already_signed->execute([
                        'event_id' => $event_id,
                        'volunteer_id' => $volunteer_id
                    ]);
                    
                    if ($already_signed->fetch()) {
                        $response['success'] = true;
                        $response['message'] = "You are already signed up for this event.";
                        $response['already_signed'] = true;
                    } else {
                        try {
                            $insert_stmt = $pdo->prepare("
                                INSERT INTO event_attendees (event_id, volunteer_id, signup_timestamp, status)
                                VALUES (:event_id, :volunteer_id, NOW(), 'registered')
                            ");
                            $insert_stmt->execute([
                                'event_id' => $event_id,
                                'volunteer_id' => $volunteer_id
                            ]);
                            
                            $response['success'] = true;
                            $response['message'] = "Successfully signed up for the event!";
                            $response['spots_remaining'] = $spots_remaining - 1;
                            
                        } catch (PDOException $e) {
                            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                $update_stmt = $pdo->prepare("
                                    UPDATE event_attendees 
                                    SET status = 'registered', signup_timestamp = NOW() 
                                    WHERE event_id = :event_id AND volunteer_id = :volunteer_id
                                ");
                                $update_stmt->execute([
                                    'event_id' => $event_id,
                                    'volunteer_id' => $volunteer_id
                                ]);
                                
                                $response['success'] = true;
                                $response['message'] = "Successfully updated your registration!";
                                $response['already_signed'] = true;
                            } else {
                                throw $e;
                            }
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $response['message'] = "An error occurred. Please try again.";
            error_log("AJAX signup error: " . $e->getMessage());
        }
    }
    
    echo json_encode($response);
    exit;
}
if (isset($_GET['success'])) {
    $success_msg = urldecode($_GET['success']);
    if (strpos($success_msg, 'Successfully') === 0) {
        $signup_message = $success_msg;
    } else {
        $signup_error = $success_msg;
    }
}
try {
    $pdo = connect_to_database();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("
        SELECT 
            event_id,
            title,
            location,
            start_date,
            slots_available,
            image_url,
            description,
            organizer_id
        FROM events
        WHERE is_approved = 1 
        AND status = 'active'
        ORDER BY start_date ASC
    ");
    
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $opportunities = [];
    foreach ($events as $event) {
        $org_name = null;
        if (!empty($event['organizer_id'])) {
            $org_stmt = $pdo->prepare("SELECT org_name FROM organizers WHERE user_id = :organizer_id");
            $org_stmt->execute(['organizer_id' => $event['organizer_id']]);
            $org_result = $org_stmt->fetch(PDO::FETCH_ASSOC);
            $org_name = $org_result ? $org_result['org_name'] : null;
        }
        
        $attendee_stmt = $pdo->prepare("
            SELECT COUNT(*) as registered_count 
            FROM event_attendees 
            WHERE event_id = :event_id AND (status = 'registered' OR status = 'confirmed')
        ");
        $attendee_stmt->execute(['event_id' => $event['event_id']]);
        $registered_count = $attendee_stmt->fetch()['registered_count'];
        
        $spots_remaining = $event['slots_available'] - $registered_count;
        
        $is_signed_up = false;
        if (isset($_SESSION['user_id'])) {
            $signup_check = $pdo->prepare("
                SELECT 1 FROM event_attendees 
                WHERE event_id = :event_id AND volunteer_id = :volunteer_id AND (status = 'registered' OR status = 'confirmed')
            ");
            $signup_check->execute([
                'event_id' => $event['event_id'],
                'volunteer_id' => $_SESSION['user_id']
            ]);
            $is_signed_up = (bool)$signup_check->fetch();
        }
        
        $opportunities[] = [
            "id"              => $event['event_id'],
            "title"           => $event['title'],
            "city"            => $event['location'],
            "date_display"    => date('M j, Y', strtotime($event['start_date'])),
            "time_display"    => null,
            "spots_remaining" => $spots_remaining,
            "image_url"       => !empty($event['image_url']) ? $event['image_url'] : $placeholderImage,
            "organization"    => $org_name,
            "description"     => $event['description'],
            "is_signed_up"    => $is_signed_up,
        ];
    }
} catch (PDOException $e) {
    $opportunities = [];
}
ob_start();
?>
<h1>Upcoming Opportunities</h1>
<p class="helper">
    Discover volunteer opportunities in your community.
</p>
<?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'organizer'): ?>
    <div style="margin-bottom: 1.5rem; text-align: right;">
        <button type="button" id="create-event-btn" class="btn" style="background-color: #28a745; color: white;">
            + Create New Event
        </button>
    </div>
<?php endif; ?>
<?php if (!empty($signup_message)): ?>
    <div class="success-message" style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
        <?php echo htmlspecialchars($signup_message); ?>
    </div>
<?php endif; ?>
<?php if (!empty($signup_error)): ?>
    <div class="error-message" style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
        <strong>Error:</strong> <?php echo htmlspecialchars($signup_error); ?>
    </div>
<?php endif; ?>
<div id="create-event-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div style="background: white; max-width: 600px; margin: 50px auto; padding: 2rem; border-radius: 8px; position: relative;">
        <button type="button" id="close-modal" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        
        <h2>Create New Event</h2>
        
        <form method="post" action="timeline.php">
            <div style="margin-bottom: 1rem;">
                <label for="event_title" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Event Title *</label>
                <input type="text" id="event_title" name="event_title" required maxlength="255" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label for="event_description" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Description *</label>
                <textarea id="event_description" name="event_description" required rows="5" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;"></textarea>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label for="event_location" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Location *</label>
                <select id="event_location" name="event_location" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="">Select a location</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo htmlspecialchars($location); ?>">
                            <?php echo htmlspecialchars($location); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label for="start_date" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Start Date & Time *</label>
                <input type="datetime-local" id="start_date" name="start_date" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label for="end_date" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">End Date & Time *</label>
                <input type="datetime-local" id="end_date" name="end_date" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label for="slots_available" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Number of Volunteer Spots *</label>
                <input type="number" id="slots_available" name="slots_available" required min="1" value="10" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label for="image_url" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Image URL (optional)</label>
                <input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                <small style="color: #666;">Provide a URL to an image for your event</small>
            </div>
            
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" id="cancel-btn" style="padding: 0.5rem 1.5rem; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                <button type="submit" name="create_event" style="padding: 0.5rem 1.5rem; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">Create Event</button>
            </div>
        </form>
    </div>
</div>
<div id="timeline" class="timeline">
    <?php if (empty($opportunities)): ?>
        <p class="helper" style="text-align: center; padding: 2rem;">
            No opportunities available at the moment. Check back soon!
        </p>
    <?php else: ?>
        <?php foreach ($opportunities as $opp): ?>
            <article class="timeline-card"
                     data-id="<?= (int)$opp["id"] ?>"
                     data-spots="<?= (int)$opp["spots_remaining"] ?>">
                <div class="timeline-card-image">
                        <img src="<?= htmlspecialchars($opp["image_url"]) ?>" alt="">
                        <?php if (!empty($opp["organization"])): ?>
                            <a class="timeline-org-badge"
                            href="#">
                                <?= htmlspecialchars($opp["organization"]) ?>
                            </a>
                        <?php endif; ?>
                </div>
                <div class="timeline-card-body">
                    <div class="timeline-card-meta">
                        <span class="timeline-date"><?= htmlspecialchars($opp["date_display"]) ?></span>
                        <?php if (!empty($opp["time_display"])): ?>
                            <span class="timeline-time"><?= htmlspecialchars($opp["time_display"]) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($opp["city"])): ?>
                            <span class="timeline-city"><?= htmlspecialchars($opp["city"]) ?></span>
                        <?php endif; ?>
                    </div>
                    <h2 class="timeline-card-title">
                        <?= htmlspecialchars($opp["title"]) ?>
                    </h2>
                    <?php if (!empty($opp["description"])): ?>
                        <p class="timeline-card-description">
                            <?= htmlspecialchars($opp["description"]) ?>
                        </p>
                    <?php endif; ?>
                    <div class="timeline-card-actions">
                        <div class="actions-left">
                            <a href="opportunity_details.php?id=<?= (int)$opp["id"] ?>" class="btn btn-outline">More details</a>
                        </div>
                        <div class="actions-right">
                            <?php if ($opp["is_signed_up"]): ?>
                                <button type="button" class="btn signed-up-btn" disabled style="background-color: #28a745; cursor: not-allowed;">
                                    ✓ Signed Up
                                </button>
                            <?php elseif ((int)$opp["spots_remaining"] <= 0): ?>
                                <button type="button" class="btn" disabled style="background-color: #6c757d; cursor: not-allowed;">
                                    Event Full
                                </button>
                            <?php else: ?>
                                <button type="button" 
                                        class="btn volunteer-btn-ajax"
                                        data-event-id="<?= (int)$opp["id"] ?>"
                                        data-event-title="<?= htmlspecialchars($opp["title"]) ?>"
                                        style="background-color: #007bff; color: white;">
                                    Volunteer
                                </button>
                            <?php endif; ?>
                            <?php if ((int)$opp["spots_remaining"] <= 5 && (int)$opp["spots_remaining"] > 0): ?>
                                <div class="spots-warning" id="spots-<?= (int)$opp["id"] ?>">
                                    Only <?= (int)$opp["spots_remaining"] ?> spots left!
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<p id="timeline-loading" class="timeline-loading helper" style="display: none;">
    Loading more opportunities...
</p>
<div class="timeline-show-more-wrap">
    <button id="timeline-show-more" type="button" class="btn btn-full">
        Show more opportunities
    </button>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const createEventBtn = document.getElementById('create-event-btn');
    const modal = document.getElementById('create-event-modal');
    const closeModal = document.getElementById('close-modal');
    const cancelBtn = document.getElementById('cancel-btn');
    
    if (createEventBtn) {
        createEventBtn.addEventListener('click', function() {
            modal.style.display = 'block';
        });
    }
    
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    document.querySelectorAll('.volunteer-btn-ajax').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            const eventTitle = this.getAttribute('data-event-title');
            const originalBtn = this;
            
            if (!confirm('Are you sure you want to sign up for \'' + eventTitle + '\'?')) {
                console.log('User cancelled signup for event: ' + eventTitle);
                return;
            }
            
            originalBtn.disabled = true;
            originalBtn.textContent = 'Signing up...';
            originalBtn.style.backgroundColor = '#6c757d';
            originalBtn.style.cursor = 'wait';
            const formData = new FormData();
            formData.append('ajax_signup_event', eventId);
            
            fetch('timeline.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    originalBtn.textContent = '✓ Signed Up';
                    originalBtn.style.backgroundColor = '#28a745';
                    originalBtn.style.cursor = 'not-allowed';
                    originalBtn.classList.remove('volunteer-btn-ajax');
                    originalBtn.classList.add('signed-up-btn');
                    
                    const spotsElement = document.getElementById('spots-' + eventId);
                    if (spotsElement) {
                        if (data.spots_remaining !== undefined && data.spots_remaining <= 0) {
                            spotsElement.textContent = 'Event Full';
                        } else if (data.spots_remaining !== undefined) {
                            spotsElement.textContent = 'Only ' + data.spots_remaining + ' spots left!';
                        }
                    }
                    
                    alert(data.message);
                } else {
                    originalBtn.disabled = false;
                    originalBtn.textContent = 'Volunteer';
                    originalBtn.style.backgroundColor = '#007bff';
                    originalBtn.style.cursor = 'pointer';
                    
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                originalBtn.disabled = false;
                originalBtn.textContent = 'Volunteer';
                originalBtn.style.backgroundColor = '#007bff';
                originalBtn.style.cursor = 'pointer';
                
                alert('An error occurred. Please try again.');
            });
        });
    });
});
</script>
<?php
$content = ob_get_clean();
include "base.php";
?>
