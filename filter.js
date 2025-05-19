document.getElementById("filter-form").addEventListener("submit", function(event) {
    event.preventDefault();

    const formData = new FormData(this);
    const filterStatus = formData.get("status");
    const search = formData.get("search");

    fetch(`/manage_loans.php?status=${filterStatus}&search=${search}`, {
        method: 'GET',
    })
    .then(response => response.text())
    .then(html => {
        document.querySelector(".loans-table tbody").innerHTML = html;
    })
    .catch(error => console.error('Error:', error));
});
