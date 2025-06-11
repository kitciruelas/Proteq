<?php
session_start();
require_once '../includes/db.php';

// Fetch all safety protocols
$sql = "SELECT *, DATE_FORMAT(created_at, '%M %d, %Y') AS formatted_date 
        FROM safety_protocols 
        ORDER BY created_at DESC";


$result = $conn->query($sql);
$protocols = $result->fetch_all(MYSQLI_ASSOC);

// Group protocols by type
$grouped_protocols = [];
foreach ($protocols as $protocol) {
    $grouped_protocols[$protocol['type']][] = $protocol;
}

// Define protocol type descriptions and colors
$type_info = [
    'fire' => [
        'description' => 'Procedures for fire emergencies, including evacuation routes and assembly points',
        'color' => '#dc3545',
        'bg_color' => '#fff5f5',
        'border_color' => '#dc3545',
        'icon' => 'fire'
    ],
    'earthquake' => [
        'description' => 'Safety measures during and after earthquakes, including drop, cover, and hold procedures',
        'color' => '#fd7e14',
        'bg_color' => '#fff8f0',
        'border_color' => '#fd7e14',
        'icon' => 'geo-alt'
    ],
    'medical' => [
        'description' => 'First aid and medical emergency response protocols',
        'color' => '#0dcaf0',
        'bg_color' => '#f0f9ff',
        'border_color' => '#0dcaf0',
        'icon' => 'heart-pulse'
    ],
    'intrusion' => [
        'description' => 'Security breach and unauthorized access response procedures',
        'color' => '#6f42c1',
        'bg_color' => '#f8f5ff',
        'border_color' => '#6f42c1',
        'icon' => 'shield-lock'
    ],
    'general' => [
        'description' => 'General safety guidelines and emergency procedures',
        'color' => '#198754',
        'bg_color' => '#f0fff4',
        'border_color' => '#198754',
        'icon' => 'shield-check'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safety Protocols - PROTEQ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/g_user.css">
    <style>
        .protocol-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            background: white;
            border-left: 4px solid;
        }
        .protocol-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .protocol-type {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid;
        }
        .protocol-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .protocol-steps {
            list-style-type: none;
            padding-left: 0;
        }
        .protocol-steps li {
            margin-bottom: 0.75rem;
            padding-left: 1.75rem;
            position: relative;
            line-height: 1.5;
        }
        .protocol-steps li:before {
            content: "•";
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        .search-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .type-filter {
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            transition: all 0.2s;
            margin: 0.25rem;
            display: inline-block;
            border: 2px solid transparent;
        }
        .type-filter:hover {
            background: #e9ecef;
        }
        .type-filter.active {
            color: white;
        }
        .protocol-description {
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .quick-actions {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }
        .quick-action-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            margin-top: 0.5rem;
            transition: all 0.2s;
        }
        .quick-action-btn:hover {
            transform: scale(1.1);
        }
        .protocol-section {
            display: none;
        }
        .protocol-section.active {
            display: block;
        }
        .emergency-contact {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .protocol-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .protocol-header i {
            font-size: 2rem;
            margin-right: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'components/_sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/topbar.php'; ?>
        <div class="container-fluid p-4">

            <!-- Search and Filter Section -->
            <div class="search-box mb-4">
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" id="protocolSearch" 
                                   placeholder="Search protocols...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-wrap" id="typeFilters">
                            <div class="type-filter active" data-type="all" style="background: #6c757d; color: white;">
                                <i class="bi bi-grid me-1"></i> All
                            </div>
                            <?php foreach ($type_info as $type => $info): ?>
                                <div class="type-filter" data-type="<?php echo htmlspecialchars($type); ?>" 
                                     style="border-color: <?php echo $info['color']; ?>; color: <?php echo $info['color']; ?>;">
                                    <i class="bi bi-<?php echo $info['icon']; ?> me-1"></i>
                                    <?php echo ucfirst(htmlspecialchars($type)); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contacts -->
            <div class="emergency-contact mb-4">
                <h5 class="mb-2">
                    <i class="bi bi-telephone-fill text-warning me-2"></i>
                    Emergency Contacts
                </h5>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Emergency Services:</strong> 911
                    </div>
                    <div class="col-md-4">
                        <strong>Security:</strong> (555) 123-4567
                    </div>
                    <div class="col-md-4">
                        <strong>Medical:</strong> (555) 987-6543
                    </div>
                </div>
            </div>

            <!-- Protocols Display -->
            <div class="row g-4" id="protocolsContainer">
                <?php foreach ($grouped_protocols as $type => $type_protocols): 
                    $info = $type_info[$type] ?? $type_info['general'];
                ?>
                    <div class="col-md-6 col-lg-4 protocol-section" data-type="<?php echo htmlspecialchars($type); ?>">
                        <div class="card protocol-card h-100" style="border-left-color: <?php echo $info['color']; ?>; background-color: <?php echo $info['bg_color']; ?>;">
                            <div class="card-body">
                                <div class="protocol-header">
                                    <i class="bi bi-<?php echo $info['icon']; ?>" style="color: <?php echo $info['color']; ?>;"></i>
                                    <div class="protocol-type" style="color: <?php echo $info['color']; ?>; border-bottom-color: <?php echo $info['color']; ?>;">
                                        <?php echo ucfirst(htmlspecialchars($type)); ?> Safety
                                    </div>
                                </div>
                                
                                <p class="protocol-description" style="color: <?php echo $info['color']; ?>;">
                                    <?php echo htmlspecialchars($info['description']); ?>
                                </p>
                                
                                <?php foreach ($type_protocols as $protocol): ?>
                                    <div class="mb-4">
                                        <h5 class="h6 mb-3" style="color: <?php echo $info['color']; ?>;">
                                            <i class="bi bi-check-circle-fill me-2"></i>
                                            <?php echo htmlspecialchars($protocol['title']); ?>
                                        </h5>
                                        <div class="protocol-steps">
                                            <?php 
                                            $steps = explode("\n", $protocol['description']);
                                            foreach ($steps as $step):
                                                if (trim($step)):
                                            ?>
                                                <li>
                                                    <?php echo htmlspecialchars(trim($step)); ?>
                                                </li>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <?php if ($protocol['file_attachment']): ?>
                                                <a href="../uploads/protocols/<?php echo htmlspecialchars($protocol['file_attachment']); ?>" 
                                                   class="btn btn-sm" 
                                                   style="background-color: <?php echo $info['color']; ?>; color: white; border: none;"
                                                   target="_blank">
                                                    <i class="bi bi-file-earmark-text me-1"></i>
                                                    View Attachment
                                                </a>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?php echo htmlspecialchars($protocol['formatted_date']); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/user-menu.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));

            // Show all protocols initially
            document.querySelectorAll('.protocol-section').forEach(section => {
                section.classList.add('active');
            });

            // Type filter functionality
            const typeFilters = document.querySelectorAll('.type-filter');
            typeFilters.forEach(filter => {
                filter.addEventListener('click', function() {
                    // Get the color from the border-color style
                    const borderColor = this.style.borderColor;
                    
                    // Update active state
                    typeFilters.forEach(f => {
                        f.classList.remove('active');
                        if (f.dataset.type === 'all') {
                            f.style.background = '#6c757d';
                            f.style.color = 'white';
                        } else {
                            f.style.background = 'transparent';
                            f.style.color = f.style.borderColor;
                        }
                    });

                    // Set active state for clicked filter
                    this.classList.add('active');
                    if (this.dataset.type === 'all') {
                        this.style.background = '#6c757d';
                        this.style.color = 'white';
                    } else {
                        this.style.background = borderColor;
                        this.style.color = 'white';
                    }

                    const selectedType = this.dataset.type;
                    
                    // Show/hide protocols based on type
                    document.querySelectorAll('.protocol-section').forEach(section => {
                        if (selectedType === 'all' || section.dataset.type === selectedType) {
                            section.classList.add('active');
                        } else {
                            section.classList.remove('active');
                        }
                    });
                });
            });

            // Search functionality
            const searchInput = document.getElementById('protocolSearch');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                document.querySelectorAll('.protocol-section').forEach(section => {
                    const content = section.textContent.toLowerCase();
                    if (content.includes(searchTerm)) {
                        section.classList.add('active');
                    } else {
                        section.classList.remove('active');
                    }
                });
            });

            // Quick action buttons
            document.querySelector('.quick-action-btn[title="Report Emergency"]').addEventListener('click', function() {
                window.location.href = 'Incident_report.php';
            });

            document.querySelector('.quick-action-btn[title="View Evacuation Map"]').addEventListener('click', function() {
                // Implement evacuation map view
                alert('Evacuation map feature coming soon!');
            });
        });
    </script>
</body>
</html>