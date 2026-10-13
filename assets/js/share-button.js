jQuery(document).ready(function($) {
    // Handle share button click
    $(document).on('click', '.share-button-trigger', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Get the modal ID from data attribute
        const modalId = $(this).data('modal');
        
        // Close any other open modals
        $('.share-modal').removeClass('active');
        
        // Open the correct modal
        $('#' + modalId).addClass('active');
        
        // Prevent body scroll
        $('body').addClass('modal-open');
    });
    
    // Close modal when clicking anywhere outside content
    $(document).on('click', '.share-modal', function(e) {
        if ($(e.target).hasClass('share-modal')) {
            $(this).removeClass('active');
            $('body').removeClass('modal-open');
        }
    });
    
    // Prevent modal close when clicking inside content
    $(document).on('click', '.share-modal-content', function(e) {
        e.stopPropagation();
    });
});