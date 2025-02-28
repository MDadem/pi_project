document.addEventListener("DOMContentLoaded", function () {
    const searchForm = document.getElementById("searchForm");

    if (searchForm) {
        searchForm.addEventListener("submit", function (event) {
            event.preventDefault();
            let formData = new FormData(this);
            for (let pair of formData.entries()) {
                console.log(pair[0] + ": " + pair[1]);
            }
            fetch(searchForm.action, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((response) => response.text())
                .then((html) => {
                    let parser = new DOMParser();
                    let doc = parser.parseFromString(html, "text/html");
                    document.getElementById("groupList").innerHTML =
                        doc.getElementById("groupList").innerHTML;
                })
                .catch((error) => console.error("Erreur lors du filtrage :", error));
        });
    }
});