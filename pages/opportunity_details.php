<?php
// pages/opportunity_details.php
session_start();
require_once '../config/database.php';

$pageTitle = "Opportunity Details";
$authPage  = false;

$opportunityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$event = null;
$organizer = null;
$organizerUser = null;
$eventSkills = [];
$attendeeCount = 0;
$spotsRemaining = 0;
$isSignedUp = false;
$error = "";

if ($opportunityId <= 0) {
    $error = "Invalid event ID.";
} else {
    try {
        $pdo = connect_to_database();
        
        $stmt = $pdo->prepare("
            SELECT event_id, organizer_id, title, description, image_url, location, 
                   start_date, end_date, slots_available, is_approved, status
            FROM events
            WHERE event_id = :event_id
        ");
        $stmt->execute(['event_id' => $opportunityId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            $error = "Event not found.";
        } elseif ($event['is_approved'] != 1 || $event['status'] != 'active') {
            $error = "This event is not currently available.";
        } else {
            $stmt = $pdo->prepare("
                SELECT org_name, org_description
                FROM organizers
                WHERE user_id = :organizer_id
            ");
            $stmt->execute(['organizer_id' => $event['organizer_id']]);
            $organizer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("
                SELECT email, phone
                FROM users
                WHERE user_id = :organizer_id
            ");
            $stmt->execute(['organizer_id' => $event['organizer_id']]);
            $organizerUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as attendee_count
                FROM event_attendees
                WHERE event_id = :event_id AND (status = 'registered' OR status = 'confirmed')
            ");
            $stmt->execute(['event_id' => $opportunityId]);
            $attendeeCount = $stmt->fetch(PDO::FETCH_ASSOC)['attendee_count'];
            
            $spotsRemaining = $event['slots_available'] - $attendeeCount;
            
            $stmt = $pdo->prepare("
                SELECT skill_id
                FROM event_skills
                WHERE event_id = :event_id
            ");
            $stmt->execute(['event_id' => $opportunityId]);
            $skillIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($skillIds as $skillId) {
                $stmt = $pdo->prepare("
                    SELECT skill_name
                    FROM skills
                    WHERE skill_id = :skill_id
                ");
                $stmt->execute(['skill_id' => $skillId]);
                $skill = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($skill) {
                    $eventSkills[] = $skill['skill_name'];
                }
            }
            
            if (isset($_SESSION['user_id'])) {
                $stmt = $pdo->prepare("
                    SELECT 1
                    FROM event_attendees
                    WHERE event_id = :event_id AND volunteer_id = :volunteer_id 
                    AND (status = 'registered' OR status = 'confirmed')
                ");
                $stmt->execute([
                    'event_id' => $opportunityId,
                    'volunteer_id' => $_SESSION['user_id']
                ]);
                $isSignedUp = (bool)$stmt->fetch();
            }
        }
    } catch (PDOException $e) {
        error_log("Opportunity details error: " . $e->getMessage());
        $error = "An error occurred loading the event details.";
    }
}

$placeholderImage = "/static/img/placeholder.jpeg";

ob_start();
?>

<?php if (!empty($error)): ?>
    <div class="error-message" style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; text-align: center;">
        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        <br><br>
        <a href="/pages/timeline.php" class="btn">← Back to Timeline</a>
    </div>
<?php else: ?>
    <div class="details-layout">
        <section class="details-main">
            <div class="details-image">
                <img src="<?php echo htmlspecialchars(!empty($event['image_url']) ? $event['image_url'] : $placeholderImage); ?>"
                     alt="<?php echo htmlspecialchars($event['title']); ?>">
            </div>
            <div class="details-header">
                <h1><?php echo htmlspecialchars($event['title']); ?></h1>
                <?php if ($organizer): ?>
                    <p class="details-org">
                        <?php echo htmlspecialchars($organizer['org_name']); ?>
                    </p>
                <?php endif; ?>
                <p class="details-meta">
                    <span><?php echo date('M j, Y', strtotime($event['start_date'])); ?></span>
                    ·
                    <span><?php echo date('g:i A', strtotime($event['start_date'])) . ' – ' . date('g:i A', strtotime($event['end_date'])); ?></span>
                    ·
                    <span><?php echo htmlspecialchars($event['location']); ?></span>
                </p>
            </div>
            
            <?php if (!empty($event['description'])): ?>
                <p class="details-short">
                    <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                </p>
            <?php endif; ?>
            
            <hr class="details-divider">
            
            <?php if ($organizer && !empty($organizer['org_description'])): ?>
                <section class="details-section">
                    <h2>About the Organization</h2>
                    <p><?php echo nl2br(htmlspecialchars($organizer['org_description'])); ?></p>
                </section>
            <?php endif; ?>
            
            <?php if (!empty($eventSkills)): ?>
                <section class="details-section">
                    <h2>Skills that fit well</h2>
                    <div class="details-tags">
                        <?php foreach ($eventSkills as $skill): ?>
                            <span class="details-tag"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </section>
        
        <aside class="details-sidebar">
            <div class="details-sidebar-card">
                <h2>Join this opportunity</h2>
                <p class="details-spots">
                    <?php if ($spotsRemaining <= 0): ?>
                        <span class="details-warning" style="color: #dc3545;">Event Full</span>
                    <?php elseif ($spotsRemaining <= 5): ?>
                        <span class="details-warning">Only <?php echo $spotsRemaining; ?> spot<?php echo $spotsRemaining == 1 ? '' : 's'; ?> left!</span>
                    <?php else: ?>
                        <span><?php echo $spotsRemaining; ?> spots available</span>
                    <?php endif; ?>
                </p>
                
                <?php if ($isSignedUp): ?>
                    <button type="button" class="btn btn-full" disabled style="background-color: #28a745; cursor: not-allowed;">
                        ✓ Already Signed Up
                    </button>
                <?php elseif ($spotsRemaining <= 0): ?>
                    <button type="button" class="btn btn-full" disabled style="background-color: #6c757d; cursor: not-allowed;">
                        Event Full
                    </button>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <button type="button" class="btn btn-full" onclick="alert('Please log in to volunteer for this event.'); window.location.href='/pages/login.php';">
                        Volunteer for this opportunity
                    </button>
                <?php elseif ($_SESSION['role'] !== 'volunteer'): ?>
                    <button type="button" class="btn btn-full" disabled style="cursor: not-allowed;">
                        Only volunteers can sign up
                    </button>
                <?php else: ?>
                    <button type="button" 
                            class="btn btn-full details-volunteer-btn"
                            data-event-id="<?php echo $opportunityId; ?>"
                            data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                        Volunteer for this opportunity
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="details-sidebar-card">
                <h2>Details</h2>
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['start_date'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($event['start_date'])) . ' – ' . date('g:i A', strtotime($event['end_date'])); ?></p>
                <p><strong>Location:</strong><br>
                    <?php echo nl2br(htmlspecialchars($event['location'])); ?>
                </p>
                <p><strong>Spots:</strong> <?php echo $spotsRemaining; ?> of <?php echo $event['slots_available']; ?> available</p>
            </div>
            
            <?php if ($organizerUser && (!empty($organizerUser['email']) || !empty($organizerUser['phone']))): ?>
                <div class="details-sidebar-card">
                    <h2>Contact</h2>
                    <?php if (!empty($organizerUser['email'])): ?>
                        <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($organizerUser['email']); ?>"><?php echo htmlspecialchars($organizerUser['email']); ?></a></p>
                    <?php endif; ?>
                    <?php if (!empty($organizerUser['phone'])): ?>
                        <p><strong>Phone:</strong> <a href="tel:<?php echo htmlspecialchars($organizerUser['phone']); ?>"><?php echo htmlspecialchars($organizerUser['phone']); ?></a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <p class="helper">
                <a href="/pages/timeline.php">← Back to timeline</a>
            </p>
        </aside>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const volunteerBtn = document.querySelector('.details-volunteer-btn');
        
        if (volunteerBtn) {
            volunteerBtn.addEventListener('click', function() {
                const eventId = this.getAttribute('data-event-id');
                const eventTitle = this.getAttribute('data-event-title');
                
                if (!confirm('Are you sure you want to sign up for "' + eventTitle + '"?')) {
                    return;
                }
                
                volunteerBtn.disabled = true;
                volunteerBtn.textContent = 'Signing up...';
                volunteerBtn.style.cursor = 'wait';
                
                const formData = new FormData();
                formData.append('ajax_signup_event', eventId);
                
                fetch('/pages/timeline.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                        volunteerBtn.disabled = false;
                        volunteerBtn.textContent = 'Volunteer for this opportunity';
                        volunteerBtn.style.cursor = 'pointer';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    volunteerBtn.disabled = false;
                    volunteerBtn.textContent = 'Volunteer for this opportunity';
                    volunteerBtn.style.cursor = 'pointer';
                });
            });
        }
    });
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . "/base.php";
?>