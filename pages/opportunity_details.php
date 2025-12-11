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
$questions = [];
$isOrganizer = false;
$isAdmin = false;
$signedUpVolunteers = [];
$userSkills = [];
$hasMatchingSkill = false;

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
                
                $isOrganizer = ($_SESSION['user_id'] == $event['organizer_id']);
                
                $stmt = $pdo->prepare("
                    SELECT 1 FROM admins WHERE user_id = :user_id
                ");
                $stmt->execute(['user_id' => $_SESSION['user_id']]);
                $isAdmin = (bool)$stmt->fetch();
                
                if ($_SESSION['role'] === 'volunteer') {
                    $stmt = $pdo->prepare("
                        SELECT skill_id
                        FROM user_skills
                        WHERE user_id = :user_id
                    ");
                    $stmt->execute(['user_id' => $_SESSION['user_id']]);
                    $userSkillIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($userSkillIds as $skillId) {
                        $stmt = $pdo->prepare("
                            SELECT skill_name
                            FROM skills
                            WHERE skill_id = :skill_id
                        ");
                        $stmt->execute(['skill_id' => $skillId]);
                        $skill = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($skill) {
                            $userSkills[] = $skill['skill_name'];
                        }
                    }
                    
                    if (!empty($skillIds) && !empty($userSkillIds)) {
                        $matchingSkills = array_intersect($skillIds, $userSkillIds);
                        $hasMatchingSkill = !empty($matchingSkills);
                    }
                }
            }
            
            if ($isOrganizer || $isAdmin) {
                $stmt = $pdo->prepare("
                    SELECT volunteer_id
                    FROM event_attendees
                    WHERE event_id = :event_id AND (status = 'registered' OR status = 'confirmed')
                    ORDER BY signup_timestamp DESC
                ");
                $stmt->execute(['event_id' => $opportunityId]);
                $volunteerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($volunteerIds as $volunteerId) {
                    $stmt = $pdo->prepare("
                        SELECT username, email, phone
                        FROM users
                        WHERE user_id = :user_id
                    ");
                    $stmt->execute(['user_id' => $volunteerId]);
                    $volunteerUser = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stmt = $pdo->prepare("
                        SELECT first_name, last_name
                        FROM volunteers
                        WHERE user_id = :user_id
                    ");
                    $stmt->execute(['user_id' => $volunteerId]);
                    $volunteerProfile = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stmt = $pdo->prepare("
                        SELECT skill_id
                        FROM user_skills
                        WHERE user_id = :user_id
                    ");
                    $stmt->execute(['user_id' => $volunteerId]);
                    $volunteerSkillIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $volunteerSkills = [];
                    foreach ($volunteerSkillIds as $skillId) {
                        $stmt = $pdo->prepare("
                            SELECT skill_name
                            FROM skills
                            WHERE skill_id = :skill_id
                        ");
                        $stmt->execute(['skill_id' => $skillId]);
                        $skill = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($skill) {
                            $volunteerSkills[] = $skill['skill_name'];
                        }
                    }
                    
                    $signedUpVolunteers[] = [
                        'username' => $volunteerUser['username'] ?? 'Unknown',
                        'first_name' => $volunteerProfile['first_name'] ?? '',
                        'last_name' => $volunteerProfile['last_name'] ?? '',
                        'email' => $volunteerUser['email'] ?? '',
                        'phone' => $volunteerUser['phone'] ?? '',
                        'skills' => $volunteerSkills
                    ];
                }
            }
            
            $stmt = $pdo->prepare("
                SELECT q.*, 
                       u.username AS volunteer_username,
                       v.first_name AS volunteer_first_name,
                       v.last_name AS volunteer_last_name,
                       ou.username AS organizer_username
                FROM questions q
                LEFT JOIN users u ON q.volunteer_id = u.user_id
                LEFT JOIN volunteers v ON q.volunteer_id = v.user_id
                LEFT JOIN users ou ON q.answered_by_organizer_id = ou.user_id
                WHERE q.event_id = :event_id
                ORDER BY q.asked_on DESC
            ");
            $stmt->execute(['event_id' => $opportunityId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Opportunity details error: " . $e->getMessage());
        $error = "An error occurred loading the event details.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    if (isset($_POST['submit_question'])) {
        $questionText = trim($_POST['question_text'] ?? '');
        
        if (!empty($questionText)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO questions (event_id, volunteer_id, question_text, asked_on)
                    VALUES (:event_id, :volunteer_id, :question_text, NOW())
                ");
                $stmt->execute([
                    'event_id' => $opportunityId,
                    'volunteer_id' => $_SESSION['user_id'],
                    'question_text' => $questionText
                ]);
                
                header("Location: opportunity_details.php?id=" . $opportunityId);
                exit;
            } catch (PDOException $e) {
                error_log("Question submission error: " . $e->getMessage());
                $error = "Failed to submit question.";
            }
        }
    }
    
    if (isset($_POST['submit_answer'])) {
        $questionId = (int)($_POST['question_id'] ?? 0);
        $answerText = trim($_POST['answer_text'] ?? '');
        
        if ($questionId > 0 && !empty($answerText) && $isOrganizer) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE questions 
                    SET answer_text = :answer_text, 
                        answered_by_organizer_id = :organizer_id
                    WHERE question_id = :question_id
                ");
                $stmt->execute([
                    'answer_text' => $answerText,
                    'organizer_id' => $_SESSION['user_id'],
                    'question_id' => $questionId
                ]);
                
                header("Location: opportunity_details.php?id=" . $opportunityId);
                exit;
            } catch (PDOException $e) {
                error_log("Answer submission error: " . $e->getMessage());
                $error = "Failed to submit answer.";
            }
        }
    }
    
    if (isset($_POST['delete_question'])) {
        $questionId = (int)($_POST['question_id'] ?? 0);
        
        if ($questionId > 0 && $isAdmin) {
            try {
                $stmt = $pdo->prepare("
                    DELETE FROM questions WHERE question_id = :question_id
                ");
                $stmt->execute(['question_id' => $questionId]);
                
                header("Location: opportunity_details.php?id=" . $opportunityId);
                exit;
            } catch (PDOException $e) {
                error_log("Question deletion error: " . $e->getMessage());
                $error = "Failed to delete question.";
            }
        }
    }
}

$placeholderImage = "/static/img/placeholder.jpeg";

ob_start();
?>

<?php if (!empty($error)): ?>
    <div class="error-message" style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; text-align: center;">
        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        <br><br>
        <a href="timeline.php" class="btn">← Back to Timeline</a>
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
                
                <div style="margin: 3rem 0;"></div>
            <?php endif; ?>
            
            <?php if (($isOrganizer || $isAdmin) && !empty($signedUpVolunteers)): ?>
                <section class="details-section">
                    <h2>Signed Up Volunteers (<?php echo count($signedUpVolunteers); ?>)</h2>
                    <?php if ($isAdmin && !$isOrganizer): ?>
                        <p style="color: #6c757d; font-style: italic; margin-top: 0.5rem;">
                            (Admin view)
                        </p>
                    <?php endif; ?>
                    
                    <div style="margin-top: 1.5rem;">
                        <?php foreach ($signedUpVolunteers as $index => $volunteer): ?>
                            <div style="border: 1px solid #e9ecef; border-radius: 6px; padding: 1.5rem; margin-bottom: 1rem; background-color: #f8f9fa;">
                                <h3 style="margin-top: 0; margin-bottom: 0.5rem; color: #007bff;">
                                    <?php 
                                    if (!empty($volunteer['first_name']) && !empty($volunteer['last_name'])) {
                                        echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']);
                                    } else {
                                        echo htmlspecialchars($volunteer['username']);
                                    }
                                    ?>
                                </h3>
                                
                                <?php if (!empty($volunteer['email']) || !empty($volunteer['phone'])): ?>
                                    <div style="margin-bottom: 0.75rem;">
                                        <?php if (!empty($volunteer['email'])): ?>
                                            <p style="margin: 0.25rem 0;">
                                                <strong>Email:</strong> 
                                                <a href="mailto:<?php echo htmlspecialchars($volunteer['email']); ?>">
                                                    <?php echo htmlspecialchars($volunteer['email']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($volunteer['phone'])): ?>
                                            <p style="margin: 0.25rem 0;">
                                                <strong>Phone:</strong> 
                                                <a href="tel:<?php echo htmlspecialchars($volunteer['phone']); ?>">
                                                    <?php echo htmlspecialchars($volunteer['phone']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($volunteer['skills'])): ?>
                                    <div>
                                        <strong>Skills:</strong>
                                        <div style="margin-top: 0.5rem;">
                                            <?php foreach ($volunteer['skills'] as $skill): ?>
                                                <span class="details-tag" style="display: inline-block; background-color: #007bff; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; margin-right: 0.5rem; margin-bottom: 0.5rem; font-size: 0.875rem;">
                                                    <?php echo htmlspecialchars($skill); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p style="margin: 0; color: #6c757d; font-style: italic;">No skills listed</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                
                <div style="margin: 3rem 0;"></div>
            <?php endif; ?>
            
            <section class="details-section">
                <h2>Questions & Answers</h2>
                
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'volunteer'): ?>
                    <div class="question-form" style="margin-bottom: 2rem; padding: 1.5rem; background-color: #f8f9fa; border-radius: 6px;">
                        <h3>Ask a Question</h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <textarea name="question_text" rows="4" 
                                          placeholder="Ask a question about this event..." 
                                          required
                                          style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px; font-family: inherit;"></textarea>
                            </div>
                            <button type="submit" name="submit_question" class="btn" 
                                    style="background-color: #007bff; color: white; padding: 0.5rem 1.5rem; border: none; border-radius: 4px; cursor: pointer;">
                                Submit Question
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <div class="questions-list">
                    <?php if (empty($questions)): ?>
                        <p style="text-align: center; color: #6c757d; padding: 2rem;">
                            No questions yet. Be the first to ask!
                        </p>
                    <?php else: ?>
                        <?php foreach ($questions as $question): ?>
                            <div class="question-item" 
                                 style="border: 1px solid #e9ecef; border-radius: 6px; padding: 1.5rem; margin-bottom: 1.5rem; background-color: white;">
                                
                                <div class="question-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <div>
                                        <strong>
                                            <?php
                                            $displayName = htmlspecialchars($question['volunteer_username']);
                                            if (!empty($question['volunteer_first_name'])) {
                                                $displayName = htmlspecialchars(
                                                    $question['volunteer_first_name'] . ' ' . $question['volunteer_last_name']
                                                );
                                            }
                                            echo $displayName;
                                            ?>
                                        </strong>
                                        <span style="color: #6c757d; font-size: 0.875rem;">
                                            asked on <?php echo date('M j, Y g:i A', strtotime($question['asked_on'])); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($isAdmin): ?>
                                        <form method="POST" action="" style="margin: 0;">
                                            <input type="hidden" name="question_id" value="<?php echo $question['question_id']; ?>">
                                            <button type="submit" name="delete_question" 
                                                    onclick="return confirm('Are you sure you want to delete this question?');"
                                                    style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 1rem;">
                                                ✕
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                
                                <p style="margin-bottom: 1rem;"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                                
                                <?php if (!empty($question['answer_text'])): ?>
                                    <div class="answer" 
                                         style="border-left: 3px solid #007bff; padding-left: 1rem; margin-top: 1rem; background-color: #f8f9fa; padding: 1rem; border-radius: 4px;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                            <strong style="color: #007bff;">
                                                <?php echo htmlspecialchars($question['organizer_username'] ?? 'Organizer'); ?>
                                            </strong>
                                            <span style="color: #6c757d; font-size: 0.875rem;">
                                                answered
                                            </span>
                                        </div>
                                        <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($question['answer_text'])); ?></p>
                                    </div>
                                <?php elseif ($isOrganizer): ?>
                                    <div class="answer-form" style="margin-top: 1rem;">
                                        <form method="POST" action="">
                                            <input type="hidden" name="question_id" value="<?php echo $question['question_id']; ?>">
                                            <div class="form-group">
                                                <textarea name="answer_text" rows="3" 
                                                          placeholder="Write your answer here..."
                                                          required
                                                          style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px; font-family: inherit;"></textarea>
                                            </div>
                                            <button type="submit" name="submit_answer" class="btn" 
                                                    style="background-color: #28a745; color: white; padding: 0.5rem 1.5rem; border: none; border-radius: 4px; cursor: pointer;">
                                                Post Answer
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
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
                    <button type="button" class="btn btn-full" onclick="alert('Please log in to volunteer for this event.'); window.location.href='login.php';">
                        Volunteer for this opportunity
                    </button>
                <?php elseif ($isOrganizer): ?>
                    <button type="button" class="btn btn-full" disabled style="background-color: #6c757d; cursor: not-allowed;">
                        Only volunteers can sign up
                    </button>
                <?php elseif ($isAdmin): ?>
                    <button type="button" class="btn btn-full" disabled style="background-color: #6c757d; cursor: not-allowed;">
                        Only volunteers can sign up
                    </button>
                <?php elseif ($_SESSION['role'] !== 'volunteer'): ?>
                    <button type="button" class="btn btn-full" disabled style="background-color: #6c757d; cursor: not-allowed;">
                        Only volunteers can sign up
                    </button>
                <?php elseif (!$hasMatchingSkill): ?>
                    <button type="button" class="btn btn-full" disabled style="background-color: #6c757d; cursor: not-allowed;" 
                            title="You need at least one matching skill to volunteer for this event">
                        No matching skills
                    </button>
                    <p style="color: #dc3545; font-size: 0.875rem; margin-top: 0.5rem; text-align: center;">
                        You need at least one of the required skills to volunteer for this event.
                    </p>
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
                <a href="timeline.php">← Back to timeline</a>
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
                
                fetch('timeline.php', {
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