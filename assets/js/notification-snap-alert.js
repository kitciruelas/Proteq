setTimeout(() => {
    const notification = document.querySelector('.notification-snap-alert');
    notification.style.animation = 'fadeOut 0.3s ease-out forwards';
    setTimeout(() => {
        notification.remove();
    }, 300);
}, 3000);

            // ERROR HANDLING

setTimeout(() => {
    const notification = document.querySelector('.notification-snap-alert');
    if (notification) {
        notification.style.animation = 'fadeOut 0.3s ease-out forwards';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
}, 3000);