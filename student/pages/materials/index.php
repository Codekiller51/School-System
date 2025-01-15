<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireStudent();

$studentId = $_SESSION['user_id'];
$subjectId = $_GET['subject'] ?? null;
$type = $_GET['type'] ?? null;

try {
    // Get student information and subjects
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            c.name as class_name
        FROM students s
        LEFT JOIN classes c ON c.level = s.class_level AND c.section = s.section
        WHERE s.id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();

    // Get student's subjects
    $stmt = $conn->prepare("
        SELECT DISTINCT
            s.id,
            s.name,
            s.code,
            t.first_name as teacher_first_name,
            t.last_name as teacher_last_name
        FROM subjects s
        JOIN teacher_subjects ts ON ts.subject_id = s.id
        JOIN teachers t ON t.id = ts.teacher_id
        WHERE ts.class_level = ? AND ts.section = ?
        ORDER BY s.name
    ");
    $stmt->execute([$student['class_level'], $student['section']]);
    $subjects = $stmt->fetchAll();

    // Build query for study materials
    $query = "
        SELECT 
            m.*,
            s.name as subject_name,
            s.code as subject_code,
            t.first_name as teacher_first_name,
            t.last_name as teacher_last_name
        FROM study_materials m
        JOIN subjects s ON s.id = m.subject_id
        JOIN teachers t ON t.id = m.teacher_id
        WHERE m.class_level = ? AND m.section = ?
    ";
    $params = [$student['class_level'], $student['section']];

    if ($subjectId) {
        $query .= " AND m.subject_id = ?";
        $params[] = $subjectId;
    }
    if ($type) {
        $query .= " AND m.type = ?";
        $params[] = $type;
    }
    $query .= " ORDER BY m.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $materials = $stmt->fetchAll();

    // Get material types for filter
    $stmt = $conn->prepare("
        SELECT DISTINCT type 
        FROM study_materials 
        WHERE class_level = ? AND section = ?
        ORDER BY type
    ");
    $stmt->execute([$student['class_level'], $student['section']]);
    $types = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Study Materials - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .subject-card {
            border-radius: 15px;
            transition: transform 0.2s;
            cursor: pointer;
        }
        .subject-card:hover {
            transform: translateY(-5px);
        }
        .material-card {
            border-radius: 10px;
            transition: all 0.2s;
        }
        .material-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .type-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .search-box {
            position: relative;
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .search-input {
            padding-left: 40px;
        }
        .file-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin-right: 15px;
        }
        .file-size {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="sidebar close">
        <?php include '../../includes/sidebar.php'; ?>
    </div>

    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">Study Materials</span>
        </div>

        <div class="container-fluid px-4">
            <!-- Search and Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <i class='bx bx-search'></i>
                                <input type="text" class="form-control search-input" 
                                       id="searchInput" placeholder="Search materials...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="subjectFilter">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>"
                                            <?php echo $subject['id'] == $subjectId ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="typeFilter">
                                <option value="">All Types</option>
                                <?php foreach ($types as $materialType): ?>
                                    <option value="<?php echo $materialType; ?>"
                                            <?php echo $materialType === $type ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($materialType); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" id="applyFilters">
                                <i class='bx bx-filter-alt'></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Quick Access -->
            <div class="row g-4 mb-4">
                <?php foreach ($subjects as $subject): ?>
                    <div class="col-xl-3 col-md-6">
                        <div class="card subject-card h-100" 
                             onclick="window.location.href='?subject=<?php echo $subject['id']; ?>'">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                                            <i class='bx bx-book text-primary bx-sm'></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($subject['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($subject['code']); ?></small>
                                    </div>
                                </div>
                                <p class="mb-0 small">
                                    Teacher: <?php 
                                    echo htmlspecialchars($subject['teacher_first_name'] . ' ' . 
                                        $subject['teacher_last_name']); 
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Materials List -->
            <div class="row" id="materialsList">
                <?php foreach ($materials as $material): ?>
                    <div class="col-12 mb-3">
                        <div class="card material-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <?php
                                    $iconClass = match ($material['type']) {
                                        'notes' => 'bx-note',
                                        'presentation' => 'bx-slideshow',
                                        'worksheet' => 'bx-task',
                                        'video' => 'bx-video',
                                        default => 'bx-file'
                                    };
                                    $bgClass = match ($material['type']) {
                                        'notes' => 'bg-primary',
                                        'presentation' => 'bg-success',
                                        'worksheet' => 'bg-warning',
                                        'video' => 'bg-danger',
                                        default => 'bg-info'
                                    };
                                    ?>
                                    <div class="file-icon <?php echo $bgClass; ?> bg-opacity-10">
                                        <i class='bx <?php echo $iconClass; ?> text-<?php echo str_replace('bg-', '', $bgClass); ?>'></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="view.php?id=<?php echo $material['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($material['title']); ?>
                                            </a>
                                        </h6>
                                        <p class="mb-0 small">
                                            <?php echo htmlspecialchars($material['subject_name']); ?> |
                                            Added by <?php 
                                            echo htmlspecialchars($material['teacher_first_name'] . ' ' . 
                                                $material['teacher_last_name']); 
                                            ?> |
                                            <span class="file-size">
                                                <?php echo formatFileSize($material['file_size']); ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="ms-3">
                                        <a href="../../<?php echo $material['file_path']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           download>
                                            <i class='bx bx-download'></i> Download
                                        </a>
                                    </div>
                                </div>
                                <span class="badge type-badge bg-<?php 
                                    echo match ($material['type']) {
                                        'notes' => 'primary',
                                        'presentation' => 'success',
                                        'worksheet' => 'warning',
                                        'video' => 'danger',
                                        default => 'info'
                                    };
                                ?>">
                                    <?php echo ucfirst($material['type']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script>
        $(document).ready(function() {
            // Filter functionality
            $('#applyFilters').click(function() {
                const subject = $('#subjectFilter').val();
                const type = $('#typeFilter').val();
                let url = window.location.pathname;
                let params = [];
                
                if (subject) params.push('subject=' + subject);
                if (type) params.push('type=' + type);
                
                if (params.length > 0) {
                    url += '?' + params.join('&');
                }
                
                window.location.href = url;
            });

            // Search functionality
            $('#searchInput').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                $('#materialsList .material-card').each(function() {
                    const title = $(this).find('h6').text().toLowerCase();
                    const subject = $(this).find('p').text().toLowerCase();
                    
                    if (title.includes(searchTerm) || subject.includes(searchTerm)) {
                        $(this).parent().show();
                    } else {
                        $(this).parent().hide();
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 1) . ' ' . $units[$pow];
}
