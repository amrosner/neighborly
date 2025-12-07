<?php
// pages/timeline.php
session_start();
require_once '../config/database.php';

$pageTitle = "Neighborly - Opportunities Timeline";
$authPage  = false;

$placeholderImage = "/static/img/placeholder.jpeg";

$signup_message = "";
$signup_error = "";

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
                    WHERE event_id = :event_id AND status = 'registered'
                ");
                $attendee_stmt->execute(['event_id' => $event_id]);
                $registered_count = $attendee_stmt->fetch()['registered_count'];
                
                $spots_remaining = $event['slots_available'] - $registered_count;
                
                if ($spots_remaining <= 0) {
                    $response['message'] = "No spots available for this event.";
                } else {
                    $already_signed = $pdo->prepare("
                        SELECT 1 FROM event_attendees 
                        WHERE event_id = :event_id AND volunteer_id = :volunteer_id AND status = 'registered'
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
            WHERE event_id = :event_id AND status = 'registered'
        ");
        $attendee_stmt->execute(['event_id' => $event['event_id']]);
        $registered_count = $attendee_stmt->fetch()['registered_count'];
        
        $spots_remaining = $event['slots_available'] - $registered_count;
        
        $is_signed_up = false;
        if (isset($_SESSION['user_id'])) {
            $signup_check = $pdo->prepare("
                SELECT 1 FROM event_attendees 
                WHERE event_id = :event_id AND volunteer_id = :volunteer_id AND status = 'registered'
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
                            <button type="button" class="btn btn-outline details-btn">More details</button>
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
    document.querySelectorAll('.details-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            alert('Event details feature coming soon!');
        });
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