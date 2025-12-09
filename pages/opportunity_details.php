<?php
// pages/opportunity_details.php
session_start();

$pageTitle = "Opportunity details";
$authPage  = false;

// display in the UI, REPLACE
$opportunityId = isset($_GET['id']) ? (int) $_GET['id'] : 1;

ob_start();
?>

<div class="details-layout">

    <!-- MAIN CONTENT -->
    <section class="details-main">
        <div class="details-image">
            <img src="/static/img/placeholder.jpeg"
                 alt="Food Bank Morning Shift">
        </div>

        <div class="details-header">
            <h1>Food Bank Morning Shift</h1>

            <p class="details-org">
                San Juan Food Bank
            </p>

            <p class="details-meta">
                <span>Mar 12, 2025</span>
                ·
                <span>10:00 AM – 1:00 PM</span>
                ·
                <span>San Juan, PR</span>
            </p>
        </div>

        <p class="details-short">
            Help organize and distribute food boxes to local families in need. This is a great
            opportunity if you like a hands-on environment and working with a small team.
        </p>

        <hr class="details-divider">

        <section class="details-section">
            <h2>What you’ll do</h2>
            <ul>
                <li>Sort and pack food donations into boxes.</li>
                <li>Help visitors find the right line or pickup area.</li>
                <li>Re-stock shelves and keep the pantry area tidy.</li>
            </ul>
        </section>

        <section class="details-section">
            <h2>Requirements</h2>
            <ul>
                <li>Comfortable with light lifting (up to 20 lbs).</li>
                <li>Basic Spanish communication.</li>
            </ul>
        </section>

        <section class="details-section">
            <h2>Skills that fit well</h2>
            <div class="details-tags">
                <span class="details-tag">Teamwork</span>
                <span class="details-tag">Organization</span>
                <span class="details-tag">Customer service</span>
            </div>
        </section>
    </section>

    <!-- SIDEBAR -->
    <aside class="details-sidebar">
        <div class="details-sidebar-card">
            <h2>Join this opportunity</h2>

            <p class="details-spots">
                <span class="details-warning">Only 3 spots left!</span>
            </p>

            <button type="button" class="btn btn-full details-volunteer-btn">
                Volunteer for this opportunity
            </button>

        </div>

        <div class="details-sidebar-card">
            <h2>Details</h2>
            <p><strong>Date:</strong> Mar 12, 2025</p>
            <p><strong>Time:</strong> 10:00 AM – 1:00 PM</p>
            <p><strong>Location:</strong><br>
                123 Community Ave<br>
                San Juan, PR
            </p>
        </div>

        <div class="details-sidebar-card">
            <h2>Contact</h2>
            <p><strong>Email:</strong> volunteer@sjfoodbank.org</p>
            <p><strong>Phone:</strong> (787) 555-1234</p>
        </div>

        <p class="helper">
            <a href="/pages/timeline.php">← Back to timeline</a>
        </p>
    </aside>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . "/base.php";