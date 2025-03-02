$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Function to update the events table
    function updateEventsTable() {
        // Show loading spinner
        $('#spinner').removeClass('d-none');
        
        $.ajax({
            url: $('#eventFilterForm').data('url'),
            method: 'GET',
            data: $('#eventFilterForm').serialize(),
            success: function(response) {
                // Update only the table body with new data
                $('#eventsTableBody').html($(response).find('#eventsTableBody').html());
                
                // Hide loading spinner
                $('#spinner').addClass('d-none');
                
                // Reinitialize tooltips
                $('[data-bs-toggle="tooltip"]').tooltip();
            },
            error: function() {
                // Hide loading spinner
                $('#spinner').addClass('d-none');
                
                // Show error message
                alert('Une erreur est survenue lors de la mise à jour des événements.');
            }
        });
    }

    // Debounce function to limit API calls
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Add event listeners with debounce
    const debouncedUpdate = debounce(updateEventsTable, 300);

    // Search input
    $('#searchQuery').on('input', debouncedUpdate);

    // Category and status filters
    $('#categoryFilter, #statusFilter').on('change', updateEventsTable);

    // Date filters
    $('#dateFrom, #dateTo').on('change', updateEventsTable);

    // Sort handling
    $('.sort-column').on('click', function(e) {
        e.preventDefault();
        
        const column = $(this).data('column');
        const currentOrder = $(this).data('order') || 'ASC';
        const newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
        
        // Update sort inputs
        $('#sortField').val(column);
        $('#sortOrder').val(newOrder);
        
        // Update arrow icon
        $('.sort-column').find('i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
        $(this).find('i').removeClass('fa-sort').addClass(newOrder === 'ASC' ? 'fa-sort-up' : 'fa-sort-down');
        
        // Update data-order attribute
        $(this).data('order', newOrder);
        
        // Update table
        updateEventsTable();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#eventFilterForm')[0].reset();
        updateEventsTable();
    });
});
