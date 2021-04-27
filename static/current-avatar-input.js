function createCurrentAvatarInput(confirmText) {
    
    const avatarSubmit = document.getElementById('avatar_submit');
    const avatarFileInput = document.getElementById('avatar_file');
    const avatarRemoveLink = document.getElementById('avatar_remove_link');
    
    avatarSubmit.style.display = 'none';
    
    avatarFileInput.addEventListener('change', function() {
        const settingsForm = document.getElementById('user_settings_form');
        settingsForm.submit();
    });

    if (avatarRemoveLink) {
        avatarRemoveLink.addEventListener('click', function (event) {
            event.preventDefault();
            if (confirm(confirmText)) {
                location.href = avatarRemoveLink.getAttribute('href');
            }
        });
    }
}