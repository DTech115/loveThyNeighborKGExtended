<?php
// fuctions for the foodsout part of the database

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
        $id = $con->insert_id;
        $stmt->close();
        return $id;
    } catch (mysqli_sql_exception $e) {
        error_log('Foodway addFoodOut failed: ' . $e->getMessage());
        return false;
    } finally {
        if ($con instanceof mysqli) {
            mysqli_close($con);
        }
    }
}

/* gets an entry so the page can confirm what was just saved
 * returns an array with customer_id & total_weight or null if there is no entry
 */
function getFoodOutEntry($entryid)
{
    $con = null;
    try {
        $con = connect();
        $query = "SELECT `customer_id`, `total_weight` FROM `goods_out_active` WHERE `entry_id` = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $entryid);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row : null;
    } catch (mysqli_sql_exception $e) {
        error_log('Foodway getFoodOutEntry failed: ' . $e->getMessage());
        return null;
    } finally {
        if ($con instanceof mysqli) {
            mysqli_close($con);
        }
    }
}
