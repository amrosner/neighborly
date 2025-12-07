<?php
// pages/timeline.php
session_start();

$pageTitle = "Neighborly - Opportunities Timeline";
$authPage  = false;

$placeholderImage = "/static/img/placeholder.jpeg";

$opportunities = [  // Sample data for demonstration, replace with database fetch
    [
        "id"              => 1,
        "title"           => "Food Bank Morning Shift",
        "city"            => "San Juan",
        "date_display"    => "Mar 12, 2025",
        "time_display"    => "10:00 AM",
        "spots_remaining" => 3,
        "image_url"       => $placeholderImage,
        "organization"    => "San Juan Food Bank",
        "description"     => "Help organize and distribute food to families.",
    ],
    [
        "id"              => 2,
        "title"           => "Beach Clean-up",
        "city"            => "Luquillo",
        "date_display"    => "Mar 15, 2025",
        "time_display"    => "2:30 PM",
        "spots_remaining" => 8,
        "image_url"       => $placeholderImage,
        "organization"    => "Luquillo Volunteers",
        "description"     => "Join volunteers to clean the coast and sort recyclables.",
    ],
    [
        "id"              => 3,
        "title"           => "After-school Tutoring",
        "city"            => "Bayamon",
        "date_display"    => "Mar 20, 2025",
        "time_display"    => "5:00 PM",
        "spots_remaining" => 5,
        "image_url"       => $placeholderImage,
        "organization"    => "Bayamon Estudiantes Unidos",
        "description"     => "Support students with homework and reading practice.",
    ],
];
ob_start();
?>
<h1>Upcoming Opportunities</h1>
<p class="helper">
    Discover volunteer opportunities in your community.
</p>

<div id="timeline" class="timeline">
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
                        <button type="button" class="btn volunteer-btn">Volunteer</button>

                        <?php if ((int)$opp["spots_remaining"] <= 5): ?>
                            <div class="spots-warning">
                                Only <?= (int)$opp["spots_remaining"] ?> spots left!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
        </article>
    <?php endforeach; ?>
</div>

<p id="timeline-loading" class="timeline-loading helper">
    Loading more opportunities...
</p>

<div class="timeline-show-more-wrap">
    <button id="timeline-show-more" type="button" class="btn btn-full">
        Show more opportunities
    </button>
</div>

<?php
$content = ob_get_clean();
include "base.php";