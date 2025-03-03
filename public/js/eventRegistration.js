document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".register-button").forEach(button => {
        button.addEventListener("click", function () {
            const eventId = this.getAttribute("data-event-id");
            fetch(`/events/register/${eventId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message); });
                }
                return response.json();
            })
            .then(data => {
                window.location.href = `/events/ticket/${data.registrationId}`;
            })
            .catch(error => alert("Error: " + error.message));
        });
    });
});
