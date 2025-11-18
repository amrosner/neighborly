// static/js/main.js

document.addEventListener("DOMContentLoaded", () => {
    const orgCheckbox = document.getElementById("is_organization");
    const orgFields   = document.getElementById("org-fields");

    if (!orgCheckbox || !orgFields) return;

    const orgName  = document.getElementById("org_name");
    const orgEmail = document.getElementById("org_email");
    const orgPhone = document.getElementById("org_phone");

    function updateOrgFields() {
        const checked = orgCheckbox.checked;

        // Show/hide section
        orgFields.style.display = checked ? "block" : "none";

        // Toggle required attributes
        if (orgName)  orgName.required  = checked;
        if (orgEmail) orgEmail.required = checked;
        if (orgPhone) orgPhone.required = checked;
    }
    // Initial update on page load
    updateOrgFields();

    // Update when checkbox is checked/unchecked
    orgCheckbox.addEventListener("change", updateOrgFields);
});