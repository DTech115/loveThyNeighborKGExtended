<?php
// functions for for the waste part of the database

include_once('dbinfo.php');


// adds a waste entry, returns the new entry_id or false if not saved
function addWaste($wastetype, $scaleweight, $cartapplied)
{
    $con = null;
    try {
        $con = connect();
        $query = "INSERT INTO `waste` (`waste_type`, `scale_weight`, `cart_applied`) VALUES (?, ?, ?)";
        $stmt = $con->prepare($query);
        // kept as strings so that float conversion doesn't ruin it
        $scale = (string)$scaleweight;
        $cart = (string)$cartapplied;
        $stmt->bind_param("sss", $wastetype, $scale, $cart);
        $stmt->execute();
        return $con->insert_id;
    } catch (Throwable $e) {
        error_log('Foodway addWaste failed: ' . $e->getMessage());
        return false;
    } finally {
        if ($con instanceof mysqli) {
            $con->close();
        }
    }
}
// gets one active entry so the page confirms what was just saved, 
// returns array with entry's columns or null if no such entry
function getWasteEntry($entryid)
{
    $con = null;
    try {
        $con = connect();
        $query = "SELECT `waste_type`, `scale_weight`, `cart_applied`, `total_weight`, `entry_date` FROM `waste_active` WHERE `entry_id` = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $entryid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $row : null;
    } catch (Throwable $e) {
        error_log('Foodway getWasteEntry failed: ' . $e->getMessage());
        return null;
    } finally {
        if ($con instanceof mysqli) {
            $con->close();
        }
    }
}

// updates an existing entry in place, recalculating total_weight, returns true if update succeeded, false otherwise
function updateWaste($entryid, $wastetype, $scaleweight, $cartapplied)
{
    $con = null;
    try {
        $con = connect();
        $query = "UPDATE `waste` SET `waste_type` = ?, `scale_weight` = ?, `cart_applied` = ? WHERE `entry_id` = ? AND `deleted_at` IS NULL";
        $stmt = $con->prepare($query);
        $scale = (string)$scaleweight;
        $cart = (string)$cartapplied;
        $stmt->bind_param("sssi", $wastetype, $scale, $cart, $entryid);
        $stmt->execute();
        return true;
    } catch (Throwable $e) {
        error_log('Foodway updateWaste failed: ' . $e->getMessage());
        return false;
    } finally {
        if ($con instanceof mysqli) {
            $con->close();
        }
    }
}