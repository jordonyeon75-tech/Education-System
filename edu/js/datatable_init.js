document.addEventListener("DOMContentLoaded", function () {
    const rowsPerPage = 5; // Number of rows to display per page

    // Function to filter rows based on the search query
    function filterRows(table, searchTerm) {
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));
        return rows.filter(row => {
            const cells = Array.from(row.querySelectorAll("td"));
            return cells.some(cell => cell.textContent.toLowerCase().includes(searchTerm));
        });
    }

    // Function to display the correct page of rows
    function displayPage(page, filteredRows, table) {
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));
        rows.forEach(row => row.style.display = "none"); // Hide all rows

        // Calculate start and end indices
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        // Show rows for the current page based on the filtered list
        filteredRows.slice(start, end).forEach(row => row.style.display = "");
    }

    // Function to create pagination for each table
    function createPagination(filteredRows, table, paginationContainer) {
        paginationContainer.innerHTML = ""; // Clear previous pagination buttons
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);

        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement("button");
            button.textContent = i;
            button.classList.add("btn", "btn-primary", "mx-1"); // Add Bootstrap classes for styling
            button.onclick = () => displayPage(i, filteredRows, table);
            paginationContainer.appendChild(button);
        }
    }

    // Initialize each table with its search and pagination functionality
    function updateTable(tableId, searchInputId, paginationId) {
        console.log(`Updating table: ${tableId}, Search: ${searchInputId}, Pagination: ${paginationId}`);
        const table = document.getElementById(tableId);
        const searchInput = document.getElementById(searchInputId);
        const paginationContainer = document.getElementById(paginationId);

        if (!table || !searchInput || !paginationContainer) {
            console.warn(`Missing elements for ${tableId}, ${searchInputId}, or ${paginationId}.`);
            return;
        }

        // Event listener for search input
        searchInput.addEventListener("input", function () {
            const searchTerm = searchInput.value.toLowerCase();
            const filteredRows = filterRows(table, searchTerm); // Filter rows based on search
            createPagination(filteredRows, table, paginationContainer); // Create pagination based on filtered rows
            displayPage(1, filteredRows, table); // Display the first page of filtered rows
        });

        // Initialize the table on page load
        const filteredRows = filterRows(table, ""); // Initially show all rows
        createPagination(filteredRows, table, paginationContainer); // Create pagination
        displayPage(1, filteredRows, table); // Display the first page
    }

    // Initialize multiple tables with their specific search and pagination elements
    updateTable("classroomTable", "searchInputClassroom", "paginationClassroom");
    updateTable("noticeTable", "searchInputNotice", "paginationNotice");
    updateTable("courseTable", "searchInputCourse", "paginationCourse");
    updateTable("userTable", "searchInputUser", "paginationUser");
    updateTable("enrollmentTable", "searchInputEnrollment", "paginationEnrollment");
    updateTable("timetableTable", "searchInputTimetable", "paginationTimetable");
});
