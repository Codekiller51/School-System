// Common functionality for all admin pages
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const toggleBtn = document.querySelector('.toggle-sidebar');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    }

    // Initialize DataTables if table exists
    if ($.fn.DataTable) {
        const tables = document.querySelectorAll('.table');
        tables.forEach(table => {
            $(table).DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
        });
    }

    // Search functionality
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            if ($.fn.DataTable) {
                const table = $('.table').DataTable();
                table.search(searchTerm).draw();
            }
        });
    }
});

// Class Management specific functionality
if (document.getElementById('classesTable')) {
    // Load teachers for the select dropdown
    function loadTeachers() {
        fetch('../api/teachers/list.php')
            .then(response => response.json())
            .then(data => {
                const select = document.querySelector('select[name="teacherId"]');
                select.innerHTML = '<option value="">Select Teacher</option>';
                data.forEach(teacher => {
                    select.innerHTML += `<option value="${teacher.id}">${teacher.first_name} ${teacher.last_name}</option>`;
                });
            })
            .catch(error => console.error('Error loading teachers:', error));
    }

    // Load classes
    function loadClasses() {
        fetch('../api/classes/list.php')
            .then(response => response.json())
            .then(data => {
                const table = $('#classesTable').DataTable();
                table.clear();
                data.forEach(classItem => {
                    table.row.add([
                        classItem.name,
                        classItem.section,
                        `${classItem.teacher_first_name} ${classItem.teacher_last_name}`,
                        classItem.total_students,
                        `<div class="btn-group">
                            <button class="btn btn-sm btn-primary edit-class" data-id="${classItem.id}">
                                <i class='bx bx-edit-alt'></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-class" data-id="${classItem.id}">
                                <i class='bx bx-trash'></i>
                            </button>
                         </div>`
                    ]).draw(false);
                });
            })
            .catch(error => console.error('Error loading classes:', error));
    }

    // Save class
    document.getElementById('saveClass').addEventListener('click', function() {
        const form = document.getElementById('addClassForm');
        const formData = new FormData(form);

        fetch('../api/classes/create.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#addClassModal').modal('hide');
                form.reset();
                loadClasses();
                // Show success message
                alert('Class added successfully!');
            } else {
                alert(data.message || 'Error adding class');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding class');
        });
    });

    // Initialize
    loadTeachers();
    loadClasses();
}

// Subject Management specific functionality
if (document.getElementById('subjectsTable')) {
    // Load subjects
    function loadSubjects() {
        fetch('../api/subjects/list.php')
            .then(response => response.json())
            .then(data => {
                const table = $('#subjectsTable').DataTable();
                table.clear();
                data.forEach(subject => {
                    table.row.add([
                        subject.subject_code,
                        subject.name,
                        subject.class_level,
                        subject.credits,
                        subject.department,
                        `<div class="btn-group">
                            <button class="btn btn-sm btn-primary edit-subject" data-id="${subject.id}">
                                <i class='bx bx-edit-alt'></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-subject" data-id="${subject.id}">
                                <i class='bx bx-trash'></i>
                            </button>
                         </div>`
                    ]).draw(false);
                });
            })
            .catch(error => console.error('Error loading subjects:', error));
    }

    // Save subject
    document.getElementById('saveSubject').addEventListener('click', function() {
        const form = document.getElementById('addSubjectForm');
        const formData = new FormData(form);

        fetch('../api/subjects/create.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#addSubjectModal').modal('hide');
                form.reset();
                loadSubjects();
                // Show success message
                alert('Subject added successfully!');
            } else {
                alert(data.message || 'Error adding subject');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding subject');
        });
    });

    // Initialize
    loadSubjects();
}

// Exam Management specific functionality
if (document.getElementById('examsTable')) {
    // Load classes and subjects for the select dropdowns
    function loadFormData() {
        // Load subjects
        fetch('../api/subjects/list.php')
            .then(response => response.json())
            .then(data => {
                const select = document.querySelector('select[name="subjectId"]');
                select.innerHTML = '<option value="">Select Subject</option>';
                data.forEach(subject => {
                    select.innerHTML += `<option value="${subject.id}">${subject.name}</option>`;
                });
            })
            .catch(error => console.error('Error loading subjects:', error));

        // Load classes
        fetch('../api/classes/list.php')
            .then(response => response.json())
            .then(data => {
                const select = document.querySelector('select[name="classId"]');
                select.innerHTML = '<option value="">Select Class</option>';
                data.forEach(classItem => {
                    select.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
                });
            })
            .catch(error => console.error('Error loading classes:', error));
    }

    // Load exams
    function loadExams() {
        fetch('../api/exams/list.php')
            .then(response => response.json())
            .then(data => {
                const table = $('#examsTable').DataTable();
                table.clear();
                data.forEach(exam => {
                    const statusClass = exam.status === 'Completed' ? 'text-success' :
                                      exam.status === 'In Progress' ? 'text-warning' : 'text-info';
                    table.row.add([
                        exam.exam_name,
                        exam.subject_name,
                        exam.class_name,
                        exam.exam_date,
                        exam.start_time,
                        `${exam.duration} mins`,
                        `<span class="${statusClass}">${exam.status}</span>`,
                        `<div class="btn-group">
                            <button class="btn btn-sm btn-primary edit-exam" data-id="${exam.id}">
                                <i class='bx bx-edit-alt'></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-exam" data-id="${exam.id}">
                                <i class='bx bx-trash'></i>
                            </button>
                         </div>`
                    ]).draw(false);
                });
            })
            .catch(error => console.error('Error loading exams:', error));
    }

    // Save exam
    document.getElementById('saveExam').addEventListener('click', function() {
        const form = document.getElementById('addExamForm');
        const formData = new FormData(form);

        fetch('../api/exams/create.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#addExamModal').modal('hide');
                form.reset();
                loadExams();
                // Show success message
                alert('Exam added successfully!');
            } else {
                alert(data.message || 'Error adding exam');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding exam');
        });
    });

    // Initialize
    loadFormData();
    loadExams();
}

// Timetable Management specific functionality
if (document.getElementById('timetableView')) {
    // Load form data (classes, subjects, teachers)
    function loadFormData() {
        // Load classes
        fetch('../api/classes/list.php')
            .then(response => response.json())
            .then(data => {
                const classSelect = document.querySelector('select[name="classId"]');
                const mainClassSelect = document.getElementById('classSelect');
                const options = '<option value="">Select Class</option>' + 
                    data.map(classItem => `<option value="${classItem.id}">${classItem.name}</option>`).join('');
                
                classSelect.innerHTML = options;
                mainClassSelect.innerHTML = options;
            })
            .catch(error => console.error('Error loading classes:', error));

        // Load subjects
        fetch('../api/subjects/list.php')
            .then(response => response.json())
            .then(data => {
                const select = document.querySelector('select[name="subjectId"]');
                select.innerHTML = '<option value="">Select Subject</option>';
                data.forEach(subject => {
                    select.innerHTML += `<option value="${subject.id}">${subject.name}</option>`;
                });
            })
            .catch(error => console.error('Error loading subjects:', error));

        // Load teachers
        fetch('../api/teachers/list.php')
            .then(response => response.json())
            .then(data => {
                const select = document.querySelector('select[name="teacherId"]');
                select.innerHTML = '<option value="">Select Teacher</option>';
                data.forEach(teacher => {
                    select.innerHTML += `<option value="${teacher.id}">${teacher.first_name} ${teacher.last_name}</option>`;
                });
            })
            .catch(error => console.error('Error loading teachers:', error));
    }

    // Load timetable for selected class
    function loadTimetable(classId) {
        if (!classId) return;

        fetch(`../api/timetable/list.php?classId=${classId}`)
            .then(response => response.json())
            .then(data => {
                const timetable = document.querySelector('#timetableView tbody');
                timetable.innerHTML = '';

                // Create time slots from 8:00 AM to 4:00 PM
                for (let hour = 8; hour <= 16; hour++) {
                    const row = document.createElement('tr');
                    const timeCell = document.createElement('td');
                    timeCell.textContent = `${hour.toString().padStart(2, '0')}:00`;
                    row.appendChild(timeCell);

                    // Add cells for each day
                    ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'].forEach(day => {
                        const cell = document.createElement('td');
                        const slots = data.filter(slot => {
                            const slotHour = parseInt(slot.start_time.split(':')[0]);
                            return slot.day === day && slotHour === hour;
                        });

                        if (slots.length > 0) {
                            slots.forEach(slot => {
                                cell.innerHTML += `
                                    <div class="schedule-item">
                                        <strong>${slot.subject_name}</strong><br>
                                        ${slot.teacher_name}<br>
                                        ${slot.start_time} - ${slot.end_time}
                                    </div>
                                `;
                            });
                        }
                        row.appendChild(cell);
                    });

                    timetable.appendChild(row);
                }
            })
            .catch(error => console.error('Error loading timetable:', error));
    }

    // Save schedule
    document.getElementById('saveSchedule').addEventListener('click', function() {
        const form = document.getElementById('addScheduleForm');
        const formData = new FormData(form);

        fetch('../api/timetable/create.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#addScheduleModal').modal('hide');
                form.reset();
                loadTimetable(document.getElementById('classSelect').value);
                alert('Schedule added successfully!');
            } else {
                alert(data.message || 'Error adding schedule');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding schedule');
        });
    });

    // Class select change handler
    document.getElementById('classSelect').addEventListener('change', function() {
        loadTimetable(this.value);
    });

    // Initialize
    loadFormData();
}

// Settings Management specific functionality
if (document.getElementById('profileForm')) {
    // Update Profile
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../api/settings/update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Profile updated successfully!');
                // Reload page to reflect changes
                window.location.reload();
            } else {
                alert(data.message || 'Error updating profile');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating profile');
        });
    });

    // Change Password
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../api/settings/change_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password changed successfully!');
                this.reset();
            } else {
                alert(data.message || 'Error changing password');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error changing password');
        });
    });

    // Update School Settings
    document.getElementById('schoolSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../api/settings/update_school.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('School settings updated successfully!');
            } else {
                alert(data.message || 'Error updating school settings');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating school settings');
        });
    });

    // Update System Preferences
    document.getElementById('preferencesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        // Add checkbox values
        formData.set('enableNotifications', document.getElementById('enableNotifications').checked ? '1' : '0');
        formData.set('enableSMS', document.getElementById('enableSMS').checked ? '1' : '0');
        formData.set('maintenanceMode', document.getElementById('maintenanceMode').checked ? '1' : '0');

        fetch('../api/settings/update_preferences.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('System preferences updated successfully!');
            } else {
                alert(data.message || 'Error updating system preferences');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating system preferences');
        });
    });
}
