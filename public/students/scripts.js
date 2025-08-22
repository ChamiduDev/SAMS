document.addEventListener('DOMContentLoaded', function() {
    // Handle delete modal data
    var deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var studentId = button.getAttribute('data-student-id');
            var studentName = button.getAttribute('data-student-name');
            
            // Update the modal's content
            var modalStudentName = deleteModal.querySelector('#modalStudentName');
            var modalStudentId = deleteModal.querySelector('#modalStudentId');
            var deleteStudentIdInput = deleteModal.querySelector('#deleteStudentId');
            
            if (modalStudentName) modalStudentName.textContent = studentName;
            if (modalStudentId) modalStudentId.textContent = studentId;
            if (deleteStudentIdInput) deleteStudentIdInput.value = studentId;
        });
    }
});
