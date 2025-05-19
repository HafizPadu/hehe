<form method="GET" id="filter-form">
    <input type="text" name="search" placeholder="Search item, loaner, or location" value="<?php echo htmlspecialchars($search); ?>" />
    <select name="status">
        <option value="">All Status</option>
        <option value="loaned" <?php if ($filter_status === 'loaned') echo 'selected'; ?>>Loaned</option>
        <option value="returned" <?php if ($filter_status === 'returned') echo 'selected'; ?>>Returned</option>
    </select>
    <button type="submit">Filter</button>
</form>
