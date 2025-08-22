// Function to load subjects for a selected course
function loadSubjects(courseSelect) {
    const courseId = courseSelect.value;
    const subjectsSelect = courseSelect.closest('.course-entry').querySelector('.subjects-select');
    
    if (!courseId) {
        subjectsSelect.innerHTML = '<option value="">-- Select Course First --</option>';
        return;
    }

    // Fetch subjects for the selected course
    fetch(`get_course_subjects.php?course_id=${courseId}`)
        .then(response => response.json())
        .then(subjects => {
            subjectsSelect.innerHTML = '';
            subjects.forEach(subject => {
                const option = new Option(subject.name, subject.id);
                subjectsSelect.add(option);
            });
        })
        .catch(error => console.error('Error loading subjects:', error));
}

// Function to add a new course entry
function addCourseEntry() {
    const container = document.getElementById('courses-container');
    const courseEntry = document.querySelector('.course-entry').cloneNode(true);
    
    // Clear selections in the cloned entry
    courseEntry.querySelector('.course-select').value = '';
    courseEntry.querySelector('.subjects-select').innerHTML = '<option value="">-- Select Course First --</option>';
    
    // Add remove button for additional entries
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-outline-danger mt-2';
    removeBtn.innerHTML = '<i class="fas fa-minus me-2"></i>Remove Course';
    removeBtn.onclick = function() {
        courseEntry.remove();
    };
    
    courseEntry.appendChild(removeBtn);
    container.appendChild(courseEntry);
    
    // Add event listener to the new course select
    const courseSelect = courseEntry.querySelector('.course-select');
    courseSelect.addEventListener('change', () => loadSubjects(courseSelect));
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener to the initial course select
    const initialCourseSelect = document.querySelector('.course-select');
    initialCourseSelect.addEventListener('change', () => loadSubjects(initialCourseSelect));
    
    // Add event listener to the "Add Another Course" button
    document.getElementById('add-course-btn').addEventListener('click', addCourseEntry);
});
