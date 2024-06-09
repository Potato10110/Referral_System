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
    $result = $conn->query("SELECT referral_code FROM referrals ORDER BY id DESC LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastCode = $row['referral_code'];
        $lastNumber = intval(substr($lastCode, strpos($lastCode, '#') + 1)) + 1;
    } else {
        $lastNumber = 1;
    }
    return 'Blesces #' . str_pad($lastNumber, 3, '0', STR_PAD_LEFT);
}

if ($action == 'create') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $referral_code = generateReferralCode($conn);

    $insertReferralQuery = "INSERT INTO referrals (name, phone, referral_code) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertReferralQuery);
    $stmt->bind_param("sss", $name, $phone, $referral_code);
    $stmt->execute();
    $stmt->close();
    header('Location: index.html');
} elseif ($action == 'read') {
    $selectReferralsQuery = "SELECT * FROM referrals ORDER BY id DESC"; // Order by ID DESC
    $result = $conn->query($selectReferralsQuery);
    $referrals = [];
    while ($row = $result->fetch_assoc()) {
        $referrals[] = $row;
    }
    echo json_encode($referrals);
}elseif ($action == 'update') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $updateReferralQuery = "UPDATE referrals SET name = ?, email = ?, phone = ? WHERE id = ?";
    $stmt = $conn->prepare($updateReferralQuery);
    $stmt->bind_param("sssi", $name, $email, $phone, $id);
    $stmt->execute();
    $stmt->close();
    header('Location: index.html');
}elseif ($action == 'delete') {
    $id = $_GET['id'];

    $deleteReferralQuery = "DELETE FROM referrals WHERE id = ?";
    $stmt = $conn->prepare($deleteReferralQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
} elseif ($action == 'search') {
    $filter = $_GET['filter'];
    $query = $_GET['query'];
    $searchReferralQuery = "SELECT * FROM referrals WHERE $filter LIKE ?";
    $likeQuery = "%" . $query . "%";
    $stmt = $conn->prepare($searchReferralQuery);
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
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
} elseif ($action == 'most_used_referral_codes') {
    $mostUsedReferralCodesQuery = "SELECT name, referral_code, referral_count FROM referrals GROUP BY referral_code ORDER BY referral_count DESC";
    $result = $conn->query($mostUsedReferralCodesQuery);
    $mostUsedReferralCodes = [];
    while ($row = $result->fetch_assoc()) {
        $mostUsedReferralCodes[] = $row;
    }
    echo json_encode($mostUsedReferralCodes);
}

$conn->close();
?>
