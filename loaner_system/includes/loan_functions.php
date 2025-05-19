<?php

function get_loans_data($page, $search, $filter_status, $limit = 10) {
    global $conn;
    $offset = ($page - 1) * $limit;
    $query = "SELECT * FROM loans WHERE 1=1";

    // Add filters
    $params = [];
    if (!empty($filter_status)) {
        $query .= " AND status = ?";
        $params[] = $filter_status;
    }
    if (!empty($search)) {
        $query .= " AND (serial_number LIKE ? OR loaner_name LIKE ? OR location LIKE ?)";
        $searchTerm = "%" . $search . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $query .= " ORDER BY start_date DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;

    // Prepare and execute query
    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat('s', count($params) - 2) . 'ii', ...$params);
    $stmt->execute();
    $loans_result = $stmt->get_result();
    $loans = $loans_result->fetch_all(MYSQLI_ASSOC);

    // Get total count for pagination
    $total_result = $conn->query("SELECT COUNT(*) as total FROM loans");
    $total_count = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total_count / $limit);

    return ['loans' => $loans, 'total_pages' => $total_pages];
}

function get_available_items() {
    global $conn;
    $result = $conn->query("SELECT serial_number, model FROM items WHERE status = 'available'");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_loaners() {
    global $conn;
    $result = $conn->query("SELECT * FROM loaners ORDER BY loaner_name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function create_loan($data) {
    global $conn;

    // Basic validation
    if (empty($data['serial_number']) || empty($data['loaner_name']) || empty($data['location']) || empty($data['department']) || empty($data['start_date']) || empty($data['return_date'])) {
        echo "<div class='message error'>Please fill in all fields.</div>";
        return;
    }

    $serial_number = $data['serial_number'];
    $loaner_id = $data['loaner_name'];
    $location = $data['location'];
    $department = $data['department'];
    $start_date = $data['start_date'];
    $return_date = $data['return_date'];

    // Fetch loaner details
    $loaner_result = $conn->query("SELECT loaner_name, department FROM loaners WHERE id = '$loaner_id'");
    $loaner = $loaner_result->fetch_assoc();

    // Transactional insert + update
    try {
        $conn->begin_transaction();

        $stmtInsert = $conn->prepare("INSERT INTO loans (serial_number, loaner_name, location, department, start_date, return_date, status) VALUES (?, ?, ?, ?, ?, ?, 'loaned')");
        $stmtInsert->bind_param("ssssss", $serial_number, $loaner['loaner_name'], $location, $department, $start_date, $return_date);
        $stmtInsert->execute();

        $stmtUpdate = $conn->prepare("UPDATE items SET status = 'loaned' WHERE serial_number = ?");
        $stmtUpdate->bind_param("s", $serial_number);
        $stmtUpdate->execute();

        $conn->commit();
        header("Location: manage_loans.php?success=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Error creating loan: " . $e->getMessage());
    }
}

?>
