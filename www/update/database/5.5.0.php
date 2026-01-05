<?php
/**
 *  5.5.0 update
 */

/**
 *  Get all user permissions
 */
$result = $this->db->query("SELECT * from user_permissions");

/**
 *  Ignore if there are no permissions
 */
if ($this->db->isempty($result)) {
    return;
}

/**
 *  Migrate each user's permissions
 */
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    try {
        $userId = $row['User_id'];
        $permissions = json_decode($row['Permissions'], true, 512, JSON_THROW_ON_ERROR);
    } catch (Exception $e) {
        throw new Exception('failed to decode user #' . $userId . ' permissions JSON: ' . $e->getMessage());
    }

    // Migrate from ['repositories']['allowed-actions']['repos'] to ['repositories']['allowed-actions']
    if (isset($permissions['repositories']['allowed-actions']['repos'])) {
        $permissions['repositories']['allowed-actions'] = $permissions['repositories']['allowed-actions']['repos'];
        unset($permissions['repositories']['allowed-actions']['repos']);
    }

    // Encode back to JSON
    try {
        $permissions = json_encode($permissions, JSON_THROW_ON_ERROR);
    } catch (Exception $e) {
        throw new Exception('failed to encode user permissions JSON: ' . $e->getMessage());
    }

    // Update the database
    $stmt = $this->db->prepare("UPDATE user_permissions SET Permissions = :permissions WHERE User_id = :userId");
    $stmt->bindValue(':permissions', $permissions, SQLITE3_TEXT);
    $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
    $stmt->execute();
}
