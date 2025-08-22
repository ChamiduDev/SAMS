// Function to fetch unread notifications count
function fetchUnreadNotificationsCount() {
    fetch('../notifications/ajax_unread.php')
        .then(response => response.json())
        .then(data => {
            const countElement = document.querySelector('.notifications-count');
            if (countElement) {
                countElement.textContent = data.count || 0;
                if (data.count === 0) {
                    countElement.style.display = 'none';
                } else {
                    countElement.style.display = 'block';
                }
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

// Update notifications count every 60 seconds
document.addEventListener('DOMContentLoaded', () => {
    fetchUnreadNotificationsCount();
    setInterval(fetchUnreadNotificationsCount, 60000);
});
