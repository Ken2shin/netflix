<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Get all available subscription plans
 */
function getSubscriptionPlans() {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM subscription_plans WHERE is_active = TRUE ORDER BY price_monthly ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting subscription plans: " . $e->getMessage());
        return [];
    }
}

/**
 * Get subscription plan by name
 */
function getSubscriptionPlan($planName) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM subscription_plans WHERE name = ? AND is_active = TRUE");
        $stmt->execute([$planName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting subscription plan: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user's current subscription info
 */
function getUserSubscription($userId) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT u.*, sp.display_name, sp.description, sp.price_monthly, sp.price_yearly, 
                   sp.max_simultaneous_streams, sp.hd_quality, sp.ultra_hd_quality, sp.download_devices, sp.features
            FROM users u 
            LEFT JOIN subscription_plans sp ON u.subscription_plan = sp.name 
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting user subscription: " . $e->getMessage());
        return null;
    }
}

/**
 * Update user's subscription plan
 */
function updateUserSubscription($userId, $planName, $paymentMethod = null) {
    try {
        $conn = getConnection();
        
        // Get plan details
        $plan = getSubscriptionPlan($planName);
        if (!$plan) {
            throw new Exception("Plan de suscripción no válido");
        }
        
        // Calculate subscription dates
        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        $stmt = $conn->prepare("
            UPDATE users SET 
                subscription_plan = ?,
                subscription_status = 'active',
                subscription_start_date = ?,
                subscription_end_date = ?,
                max_profiles = ?,
                payment_method = ?,
                last_payment_date = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $planName,
            $startDate,
            $endDate,
            $plan['max_profiles'],
            $paymentMethod,
            $userId
        ]);
        
    } catch (Exception $e) {
        error_log("Error updating user subscription: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user can create more profiles
 */
function canCreateProfile($userId) {
    try {
        $conn = getConnection();
        
        // Get user's max profiles limit
        $stmt = $conn->prepare("SELECT max_profiles FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        // Count current profiles
        $stmt = $conn->prepare("SELECT COUNT(*) as profile_count FROM profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['profile_count'] < $user['max_profiles'];
        
    } catch (Exception $e) {
        error_log("Error checking profile limit: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user's subscription is active
 */
function isSubscriptionActive($userId) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT subscription_status, subscription_end_date 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        // Check if subscription is active and not expired
        if ($user['subscription_status'] === 'active' || $user['subscription_status'] === 'trial') {
            if ($user['subscription_end_date'] && strtotime($user['subscription_end_date']) > time()) {
                return true;
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error checking subscription status: " . $e->getMessage());
        return false;
    }
}

/**
 * Add payment record
 */
function addPaymentRecord($userId, $planName, $amount, $paymentMethod, $transactionId = null) {
    try {
        $conn = getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO payment_history 
            (user_id, subscription_plan, amount, payment_method, payment_status, transaction_id, billing_period_start, billing_period_end) 
            VALUES (?, ?, ?, ?, 'completed', ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
        ");
        
        return $stmt->execute([
            $userId,
            $planName,
            $amount,
            $paymentMethod,
            $transactionId
        ]);
        
    } catch (Exception $e) {
        error_log("Error adding payment record: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's payment history
 */
function getUserPaymentHistory($userId, $limit = 10) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT ph.*, sp.display_name as plan_display_name
            FROM payment_history ph
            LEFT JOIN subscription_plans sp ON ph.subscription_plan = sp.name
            WHERE ph.user_id = ?
            ORDER BY ph.payment_date DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting payment history: " . $e->getMessage());
        return [];
    }
}

/**
 * Cancel user subscription
 */
function cancelSubscription($userId) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("
            UPDATE users SET 
                subscription_status = 'cancelled'
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Error cancelling subscription: " . $e->getMessage());
        return false;
    }
}

/**
 * Get subscription statistics for admin
 */
function getSubscriptionStats() {
    try {
        $conn = getConnection();
        
        $stats = [];
        
        // Total subscribers by plan
        $stmt = $conn->prepare("
            SELECT subscription_plan, COUNT(*) as count 
            FROM users 
            WHERE subscription_status IN ('active', 'trial')
            GROUP BY subscription_plan
        ");
        $stmt->execute();
        $stats['by_plan'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Total revenue this month
        $stmt = $conn->prepare("
            SELECT SUM(amount) as total_revenue 
            FROM payment_history 
            WHERE payment_status = 'completed' 
            AND MONTH(payment_date) = MONTH(CURRENT_DATE())
            AND YEAR(payment_date) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute();
        $stats['monthly_revenue'] = $stmt->fetchColumn() ?: 0;
        
        // Active subscriptions
        $stmt = $conn->prepare("
            SELECT COUNT(*) as active_count 
            FROM users 
            WHERE subscription_status = 'active'
        ");
        $stmt->execute();
        $stats['active_subscriptions'] = $stmt->fetchColumn() ?: 0;
        
        // Trial subscriptions
        $stmt = $conn->prepare("
            SELECT COUNT(*) as trial_count 
            FROM users 
            WHERE subscription_status = 'trial'
        ");
        $stmt->execute();
        $stats['trial_subscriptions'] = $stmt->fetchColumn() ?: 0;
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting subscription stats: " . $e->getMessage());
        return [];
    }
}
?>
