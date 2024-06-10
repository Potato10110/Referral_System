<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "referrals_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function generateReferralCode($conn) {
    $result = $conn->query("SELECT referral_code FROM referrals ORDER BY id DESC LIMIT 1 ");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastCode = $row['referral_code'];
        $lastNumber = intval(substr($lastCode, strpos($lastCode, '#') + 1)) + 1;
    } else {
        $lastNumber = 1;
    }
    return 'Blesces #' . str_pad($lastNumber, 3, '0', STR_PAD_LEFT);
}

function getImageData($conn) {
    $result = $conn->query("SELECT image FROM images WHERE id = 1"); // Assuming the image ID is 1
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['image'];
    } else {
        return null;
    }
}
function fetchAllReferrals($conn) {
    $selectReferralsQuery = "SELECT * FROM referrals";
    $result = $conn->query($selectReferralsQuery);
    $referrals = [];
    while ($row = $result->fetch_assoc()) {
        $referrals[] = $row;
    }
    return $referrals;
}


if ($action == 'create') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $referral_code = generateReferralCode($conn);

    $insertReferralQuery = "INSERT INTO referrals (name, phone, referral_code) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertReferralQuery);
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("sss", $name, $phone, $referral_code);
    $stmt->execute();
    if ($stmt->error) {
        die("Execute failed: " . htmlspecialchars($stmt->error));
    }
    $stmt->close();
    header('Location: index.html');
} elseif ($action == 'read') {
    $selectReferralsQuery = "SELECT * FROM referrals ORDER BY id DESC";
    $result = $conn->query($selectReferralsQuery);
    $referrals = [];
    while ($row = $result->fetch_assoc()) {
        $referrals[] = $row;
    }
    echo json_encode($referrals);
} elseif ($action == 'update') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];

    $updateReferralQuery = "UPDATE referrals SET name = ?, phone = ? WHERE id = ?";
    $stmt = $conn->prepare($updateReferralQuery);
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("ssi", $name, $phone, $id);
    $stmt->execute();
    if ($stmt->error) {
        die("Execute failed: " . htmlspecialchars($stmt->error));
    }
    $stmt->close();
    header('Location: index.html');
} elseif ($action == 'delete') {
    $id = $_GET['id'];

    $deleteReferralQuery = "DELETE FROM referrals WHERE id = ?";
    $stmt = $conn->prepare($deleteReferralQuery);
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->error) {
        die("Execute failed: " . htmlspecialchars($stmt->error));
    }
    $stmt->close();
    echo json_encode(['success' => true]);
} elseif ($action == 'search') {
    $filter = $_GET['filter'];
    $query = $_GET['query'];
    $searchReferralQuery = "SELECT * FROM referrals WHERE $filter LIKE ?";
    $likeQuery = "%" . $query . "%";
    $stmt = $conn->prepare($searchReferralQuery);
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("s", $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    $referrals = [];
    while ($row = $result->fetch_assoc()) {
        $referrals[] = $row;
    }
    echo json_encode($referrals);
    $stmt->close();
} elseif ($action == 'increment_count') {
    $id = $_GET['id'];
    $incrementCountQuery = "UPDATE referrals SET referral_count = referral_count + 1 WHERE id = ?";
    $stmt = $conn->prepare($incrementCountQuery);
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->error) {
        die("Execute failed: " . htmlspecialchars($stmt->error));
    }
    $stmt->close();
    echo json_encode(['success' => true]);
} elseif ($action == 'most_used_referral_codes') {
    $mostUsedReferralCodesQuery = "SELECT name, referral_code, referral_count FROM referrals ORDER BY referral_count DESC";
    $result = $conn->query($mostUsedReferralCodesQuery);
    $mostUsedReferralCodes = [];
    while ($row = $result->fetch_assoc()) {
        $mostUsedReferralCodes[] = $row;
    }
    echo json_encode($mostUsedReferralCodes);
}elseif ($action == 'print_all_data') {
    // Fetch all referrals
    $referrals = fetchAllReferrals($conn);

    // Generate printable HTML
    $printableHtml = "<html><head><title>Referral Data</title>";
    $printableHtml .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">';
    $printableHtml .= "</head><body>";
    $printableHtml .= "<div class='container'>";
    $printableHtml .= "<h1 class='mt-5 mb-4'>Referral Data</h1>";
    $printableHtml .= "<table class='table table-striped'>";
    $printableHtml .= "<thead><tr><th>Name</th><th>Phone</th><th>Referral Code</th><th>Created At</th></tr></thead>";
    $printableHtml .= "<tbody>";
    foreach ($referrals as $referral) {
    $createdAt = date('Y-m-d H:i:s', strtotime($referral['created_at']));
    $printableHtml .= "<tr><td>{$referral['name']}</td><td>{$referral['phone']}</td><td>{$referral['referral_code']}</td><td>{$createdAt}</td></tr>";
    }
    $printableHtml .= "</tbody>";
    $printableHtml .= "</table>";
    $printableHtml .= "</div>"; // Close container
    $printableHtml .= "</body></html>";

    echo $printableHtml;
}
$conn->close();
?>
    