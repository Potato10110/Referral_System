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

function generateReferralCode() {
    return substr(md5(uniqid(mt_rand(), true)), 0, 8);
}

if ($action == 'create') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $referral_code = generateReferralCode();

    $insertReferralQuery = "INSERT INTO referrals (name, email, phone, referral_code) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertReferralQuery);
    $stmt->bind_param("ssss", $name, $email, $phone, $referral_code);
    $stmt->execute();
    $stmt->close();
    header('Location: index.html');
} elseif ($action == 'read') {
    $selectReferralsQuery = "SELECT * FROM referrals";
    $result = $conn->query($selectReferralsQuery);
    $referrals = [];
    while ($row = $result->fetch_assoc()) {
        $referrals[] = $row;
    }
    echo json_encode($referrals);
} elseif ($action == 'update') {
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
} elseif ($action == 'delete') {
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
} elseif ($action == 'create_link') {
    $referral_code = $_POST['referral_code'];
    $checkReferralQuery = "SELECT * FROM referrals WHERE referral_code = ?";
    $stmt = $conn->prepare($checkReferralQuery);
    $stmt->bind_param("s", $referral_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $referral_link = $_POST['referral_link'];
        $insertReferralLinkQuery = "INSERT INTO referral_links (referral_link) VALUES (?)";
        $stmt = $conn->prepare($insertReferralLinkQuery);
        $stmt->bind_param("s", $referral_link);
        $stmt->execute();
        $stmt->close();
        header('Location: referral_link.html');
    } else {
        echo "Invalid referral code. Please enter a valid referral code.";
    }
} elseif ($action == 'read_links') {
    $selectReferralLinksQuery = "SELECT * FROM referral_links";
    $result = $conn->query($selectReferralLinksQuery);
    $referral_links = [];
    while ($row = $result->fetch_assoc()) {
        $referral_links[] = $row;
    }
    echo json_encode($referral_links);
} elseif ($action == 'delete_link') {
    $id = $_GET['id'];

    $deleteReferralLinkQuery = "DELETE FROM referral_links WHERE id = ?";
    $stmt = $conn->prepare($deleteReferralLinkQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
}

$conn->close();
?>
