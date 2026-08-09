<?php
// FILE: footer.php
?>
</main>

<!-- Chatbot floating button -->
<div class="chatbot-btn" id="chatbot-btn">
    <i class="fa-solid fa-robot"></i>
</div>

<!-- Chatbot Window -->
<div class="chatbot-window" id="chatbot-window">
    <div class="chatbot-header">
        <span>StudyTrack Helper</span>
        <button id="chatbot-close">&times;</button>
    </div>

    <div class="chatbot-body" id="chatbot-body">
        <div class="chatbot-message bot">
            Hi! Ask me anything about CGPA, Scholarships, IELTS or Admission.
        </div>
    </div>

    <div class="chatbot-footer">
        <input type="text" id="chat-input" placeholder="Type your question...">
        <button id="chat-send"><i class="fa-solid fa-paper-plane"></i></button>
    </div>
</div>

<!-- Dropdown toggles (Profile + Notifications) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const profile      = document.querySelector('.profile-dropdown');
    const notification = document.querySelector('.notification-dropdown');

    // Helper: toggle open on click
    function setupDropdown(dropdown) {
        if (!dropdown) return;
        const menu = dropdown.querySelector('.dropdown-menu');

        dropdown.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = dropdown.classList.contains('open');
            // close all first
            if (profile) profile.classList.remove('open');
            if (notification) notification.classList.remove('open');
            // then open this one if it was closed
            if (!isOpen) dropdown.classList.add('open');
        });

        if (menu) {
            menu.addEventListener('click', function (e) {
                e.stopPropagation(); // keep open when clicking inside
            });
        }
    }

    setupDropdown(profile);
    setupDropdown(notification);

    // Click anywhere else => close both
    document.addEventListener('click', function () {
        if (profile) profile.classList.remove('open');
        if (notification) notification.classList.remove('open');
    });
});
</script>

<!-- Chatbot JS -->
<script src="assets/chat.js"></script>

</div> <!-- page-wrapper -->
</body>
</html>
