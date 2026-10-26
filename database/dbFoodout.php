<?php
/*
 * purpose: to manage the functions necessary to interact with the goods_out table in the
 *  database for LTN (Foodway: food out)
 */

include_once('dbinfo.php');

/* adds one food out entry.
 * returns the new entry_id, or false if the entry was not saved.
 * total_weight is calculated by the database (scale weight minus cart), so it is not passed in.
 */
function addFoodOut($customerid, $scaleweight, $cartapplied, $householdcount)
{
    $con = null;
    try {
        $con = connect();
        $query = "INSERT INTO `goods_out` (`customer_id`, `scale_weight`, `cart_applied`, `household_count`) VALUES (?, ?, ?, ?)";
        $stmt = $con->prepare($query);
        // weights are bound as strings so DECIMAL values are not rounded through a float
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

/* gets one active (not deleted) entry so the page can confirm what was just saved.
 * returns an array with customer_id and total_weight, or null if there is no such entry.
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
