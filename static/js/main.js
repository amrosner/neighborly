// static/js/main.js

document.addEventListener("DOMContentLoaded", () => {
    // ===========================
    // 1. Organization checkbox logic (register page)
    // ===========================
    const orgCheckbox = document.getElementById("is_organization");
    const orgFields   = document.getElementById("org-fields");

    if (orgCheckbox && orgFields) {
        const orgName  = document.getElementById("org_name");
        const orgEmail = document.getElementById("org_email");
        const orgPhone = document.getElementById("org_phone");

        function updateOrgFields() {
            const checked = orgCheckbox.checked;
            orgFields.style.display = checked ? "block" : "none";

            if (orgName)  orgName.required  = checked;
            if (orgEmail) orgEmail.required = checked;
            if (orgPhone) orgPhone.required = checked;
        }

        updateOrgFields();
        orgCheckbox.addEventListener("change", updateOrgFields);
    }

    // ===========================
    // 2. Timeline: Volunteer button + Show more + Loading message
    // ===========================
    const timeline       = document.getElementById("timeline");
    const showMoreButton = document.getElementById("timeline-show-more");
    const loadingText    = document.getElementById("timeline-loading");

    if (timeline) {

        // Volunteer button logic
        timeline.addEventListener("click", (event) => {
            const btn = event.target.closest(".volunteer-btn");
            if (!btn) return;

            btn.textContent = "You volunteered!";
            btn.disabled = true;
            btn.classList.add("volunteered");
        });

        // Show more button logic
        if (showMoreButton && loadingText) {
            let hasClickedOnce = false;

            showMoreButton.addEventListener("click", () => {
                if (hasClickedOnce) return;
                hasClickedOnce = true;

                // Show loading text
                loadingText.textContent = "Loading more opportunities...";
                loadingText.style.display = "block";

                // Disable button while loading
                showMoreButton.disabled = true;

                // Simulate short loading
                setTimeout(() => {
                    // Update final message
                    loadingText.textContent = "No more opportunities found.";

                    // Hide show more button completely
                    showMoreButton.style.display = "none";
                }, 800);
            });
        }
    }
});