<?php
// pages/timeline.php
session_start();
require_once '../config/database.php';

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = "Neighborly - Opportunities Timeline";
$authPage  = false;

$placeholderImage = "/static/img/placeholder.jpeg";

// Handle volunteer signup via GET parameter (more reliable)
$signup_message = "";
$signup_error = "";

error_log("=== TIMELINE DEBUG ===");
error_log("REQUEST METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("QUERY STRING: " . ($_SERVER['QUERY_STRING'] ?? 'none'));

// Check for signup via GET parameter
if (isset($_GET['signup_event'])) {
    $event_id = (int)$_GET['signup_event'];
    error_log("GET signup_event detected: $event_id");
    
    if (!isset($_SESSION['user_id'])) {
        $signup_error = "Please log in to volunteer for events.";
    } elseif ($_SESSION['role'] !== 'volunteer') {
        $signup_error = "Only volunteers can sign up for events.";
    } elseif ($event_id <= 0) {
        $signup_error = "Invalid event ID.";
    } else {
        $volunteer_id = $_SESSION['user_id'];
        
        try {
            $pdo = connect_to_database();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if event exists
            $event_check = $pdo->prepare("SELECT event_id FROM events WHERE event_id = :event_id AND status = 'active'");
            $event_check->execute(['event_id' => $event_id]);
            
            if (!$event_check->fetch()) {
                $signup_error = "Event not found or not active.";
            } else {
                // Try to insert
                echo "WE HIT";
                echo "WE HIT";
                echo "WE HIT";
                echo "WE HIT";
                echo "WE HIT";
                try {
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO event_attendees (event_id, volunteer_id, signup_timestamp, status)
                        VALUES (:event_id, :volunteer_id, NOW(), 'registered')
                    ");
                    $insert_stmt->execute([
                        'event_id' => $event_id,
                        'volunteer_id' => $volunteer_id
                    ]);
                    $signup_message = "Successfully signed up for the event!";
                    
                } catch (PDOException $e) {
                    // If duplicate, update instead
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
                        $signup_message = "Successfully updated your registration!";
                    } else {
                        throw $e;
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $signup_error = "Database error: " . $e->getMessage();
        }
    }
    
    // Remove the signup parameter to prevent re-submission on refresh
    header("Location: timeline.php?success=" . urlencode($signup_message ?: $signup_error));
    exit;
}

// Check for success/error messages from redirect
if (isset($_GET['success'])) {
    $success_msg = urldecode($_GET['success']);
    if (strpos($success_msg, 'Successfully') === 0) {
        $signup_message = $success_msg;
    } else {
        $signup_error = $success_msg;
    }
}

// Load events
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
    error_log("Main timeline query error: " . $e->getMessage());
    $opportunities = [];
}

ob_start();
?>
<h1>Upcoming Opportunities</h1>
<p class="helper">
    Discover volunteer opportunities in your community.
</p>

<!-- DEBUG INFO -->
<div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
    <h4 style="margin-top: 0; color: #6c757d;">Debug Information</h4>
    <div style="font-family: monospace; font-size: 0.9em;">
        <div><strong>Request Method:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_METHOD']); ?></div>
        <div><strong>Session User ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id'] ?? 'Not set'); ?></div>
        <div><strong>Session Role:</strong> <?php echo htmlspecialchars($_SESSION['role'] ?? 'Not set'); ?></div>
        <div><strong>Signup Message:</strong> <?php echo htmlspecialchars($signup_message ?: 'None'); ?></div>
        <div><strong>Signup Error:</strong> <?php echo htmlspecialchars($signup_error ?: 'None'); ?></div>
    </div>
</div>

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
                                <button type="button" class="btn" disabled style="background-color: #28a745; cursor: not-allowed;">
                                    ✓ Signed Up
                                </button>
                            <?php elseif ((int)$opp["spots_remaining"] <= 0): ?>
                                <button type="button" class="btn" disabled style="background-color: #6c757d; cursor: not-allowed;">
                                    Event Full
                                </button>
                            <?php else: ?>
                                <!-- SIMPLE LINK APPROACH - Will definitely work -->
                                <a href="timeline.php?signup_event=<?= (int)$opp["id"] ?>" 
                                   class="btn volunteer-btn"
                                   onclick="return confirm('Are you sure you want to sign up for \'<?= addslashes($opp["title"]) ?>\'?')">
                                    Volunteer
                                </a>
                            <?php endif; ?>

                            <?php if ((int)$opp["spots_remaining"] <= 5 && (int)$opp["spots_remaining"] > 0): ?>
                                <div class="spots-warning">
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

<!-- TEST LINKS - Always visible -->
<div style="border: 3px solid #007bff; padding: 20px; margin: 30px 0; background: #e7f3ff; border-radius: 8px;">
    <h3 style="color: #007bff; margin-top: 0;">Test Links (Should Work)</h3>
    <p>Click these links to test signup functionality:</p>
    
    <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
        <?php if (!empty($opportunities)): ?>
            <?php for($i = 0; $i < min(3, count($opportunities)); $i++): ?>
                <a href="timeline.php?signup_event=<?= $opportunities[$i]["id"] ?>" 
                   class="btn" 
                   style="background-color: <?= ['#007bff', '#28a745', '#fd7e14'][$i] ?>; color: white;">
                    Test Signup: <?= htmlspecialchars($opportunities[$i]["title"]) ?>
                </a>
            <?php endfor; ?>
        <?php endif; ?>
        
        <a href="timeline.php?signup_event=999" class="btn" style="background-color: #dc3545; color: white;">
            Test Invalid Event
        </a>
    </div>
    
    <p style="font-size: 0.9em; color: #6c757d;">
        <strong>Note:</strong> These links use GET parameters instead of forms. After clicking, you'll be redirected back here.
    </p>
</div>

<p id="timeline-loading" class="timeline-loading helper" style="display: none;">
    Loading more opportunities...
</p>

<div class="timeline-show-more-wrap">
    <button id="timeline-show-more" type="button" class="btn btn-full">
        Show more opportunities
    </button>
</div>

<!-- MINIMAL JAVASCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Timeline page loaded - using link-based signup');
    
    // Simple handler for "More details" buttons
    document.querySelectorAll('.details-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            alert('Event details feature coming soon!');
        });
    });
    
    // Log when volunteer links are clicked
    document.querySelectorAll('.volunteer-btn').forEach(function(link) {
        link.addEventListener('click', function(e) {
            console.log('Volunteer link clicked: ' + this.href);
            // Let the link work normally
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include "base.php";