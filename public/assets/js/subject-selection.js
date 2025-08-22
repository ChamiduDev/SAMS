document.addEventListener('DOMContentLoaded', function() {
    const subjectCheckboxes = document.querySelectorAll('.subject-checkbox');
    const coursesById = new Map();

    // Organize subjects by course
    subjectCheckboxes.forEach(checkbox => {
        const courseId = checkbox.dataset.courseId;
        if (!coursesById.has(courseId)) {
            coursesById.set(courseId, []);
        }
        coursesById.get(courseId).push(checkbox);
    });

    // Add click handler to checkboxes
    subjectCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // You can add validation logic here if needed
            // For example, limit the number of subjects per course
            const courseId = this.dataset.courseId;
            const selectedInCourse = coursesById.get(courseId).filter(cb => cb.checked).length;
            
            if (selectedInCourse > 5) { // Example: limit to 5 subjects per course
                this.checked = false;
                alert('You can select up to 5 subjects per course.');
            }
        });
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const totalSelected = Array.from(subjectCheckboxes).filter(cb => cb.checked).length;
        if (totalSelected === 0) {
            e.preventDefault();
            alert('Please select at least one subject.');
        }
    });
});
