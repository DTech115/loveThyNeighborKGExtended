<?php
// fuctions for the shopping part of the database


include_once('dbinfo.php');

// adds a food out entry, returns the new entry_id or flase if not saved
function addFoodOut($customerid, $scaleweight, $cartapplied, $householdcount)
{
    $con = null;
    try {
        $con = connect();
        $query = "INSERT INTO `goods_out` (`customer_id`, `scale_weight`, `cart_applied`, `household_count`) VALUES (?, ?, ?, ?)";
        $stmt = $con->prepare($query);
        // kept as strings so that float conversion doesn't ruin it
        $scale = (string)$scaleweight;
        $cart = (string)$cartapplied;
        $stmt->bind_param("issi", $customerid, $scale, $cart, $householdcount);
        $stmt->execute();
        return $con->insert_id;
    } catch (Throwable $e) {
        error_log('Foodway addFoodOut failed: ' . $e->getMessage());
        return false;
    } finally {
        if ($con instanceof mysqli) {
            $con->close();
        }
    }
}

// gets one active entry so the page confirms what was just saved, 
// returns array with entry's columns or null if no such entry
function getFoodOutEntry($entryid)
{
    $con = null;
    try {
        $con = connect();
        $query = "SELECT `customer_id`, `scale_weight`, `cart_applied`, `total_weight`, `household_count`, `entry_date` FROM `goods_out_active` WHERE `entry_id` = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $entryid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $row : null;
    } catch (Throwable $e) {
        error_log('Foodway getFoodOutEntry failed: ' . $e->getMessage());
        return null;
    } finally {
        if ($con instanceof mysqli) {
            $con->close();
        }
    }
}

// updates an existing entry in place, recalculating total_weight, returns true if update succeeded, false otherwise
function updateFoodOut($entryid, $customerid, $scaleweight, $cartapplied, $householdcount)
{
    $con = null;
    try {
        $con = connect();
        $query = "UPDATE `goods_out` SET `customer_id` = ?, `scale_weight` = ?, `cart_applied` = ?, `household_count` = ? WHERE `entry_id` = ? AND `deleted_at` IS NULL";
        $stmt = $con->prepare($query);
        $scale = (string)$scaleweight;
        $cart = (string)$cartapplied;
        $stmt->bind_param("issii", $customerid, $scale, $cart, $householdcount, $entryid);
        $stmt->execute();
        return true;
    } catch (Throwable $e) {
        error_log('Foodway updateFoodOut failed: ' . $e->getMessage());
        return false;
    } finally {
        if ($con instanceof mysqli) {
            $con->close();
        }
    }
}