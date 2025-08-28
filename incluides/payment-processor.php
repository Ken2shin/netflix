<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/subscription-functions.php';

/**
 * Mock Payment Processor for Netflix Clone
 * This simulates real payment processing for development/demo purposes
 */
class PaymentProcessor {
    
    private $supportedMethods = ['credit_card', 'debit_card', 'paypal', 'apple_pay', 'google_pay'];
    
    /**
     * Process a payment (mock implementation)
     */
    public function processPayment($paymentData) {
        try {
            // Validate payment data
            $this->validatePaymentData($paymentData);
            
            // Simulate payment processing delay
            usleep(rand(500000, 2000000)); // 0.5-2 seconds
            
            // Mock payment success/failure (95% success rate)
            $success = rand(1, 100) <= 95;
            
            if (!$success) {
                throw new Exception('Pago rechazado por el banco. Por favor, verifica tus datos o intenta con otra tarjeta.');
            }
            
            // Generate mock transaction ID
            $transactionId = 'TXN_' . strtoupper(uniqid()) . '_' . time();
            
            // Create payment record
            $paymentRecord = [
                'transaction_id' => $transactionId,
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'USD',
                'payment_method' => $paymentData['payment_method'],
                'status' => 'completed',
                'processed_at' => date('Y-m-d H:i:s'),
                'card_last_four' => $this->getCardLastFour($paymentData),
                'card_brand' => $this->getCardBrand($paymentData),
                'billing_info' => $this->getBillingInfo($paymentData)
            ];
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_record' => $paymentRecord,
                'message' => 'Pago procesado exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'transaction_id' => null
            ];
        }
    }
    
    /**
     * Validate payment data
     */
    private function validatePaymentData($data) {
        $required = ['amount', 'payment_method', 'user_id', 'subscription_plan'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Campo requerido faltante: $field");
            }
        }
        
        if (!in_array($data['payment_method'], $this->supportedMethods)) {
            throw new Exception('Método de pago no soportado');
        }
        
        if ($data['amount'] <= 0) {
            throw new Exception('El monto debe ser mayor a cero');
        }
        
        // Validate credit card data if payment method is card
        if (in_array($data['payment_method'], ['credit_card', 'debit_card'])) {
            $this->validateCardData($data);
        }
    }
    
    /**
     * Validate credit card data
     */
    private function validateCardData($data) {
        $required = ['card_number', 'expiry_month', 'expiry_year', 'cvv', 'cardholder_name'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Información de tarjeta incompleta: $field");
            }
        }
        
        // Basic card number validation (Luhn algorithm simulation)
        $cardNumber = preg_replace('/\D/', '', $data['card_number']);
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            throw new Exception('Número de tarjeta inválido');
        }
        
        // Expiry date validation
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        if ($data['expiry_year'] < $currentYear || 
            ($data['expiry_year'] == $currentYear && $data['expiry_month'] < $currentMonth)) {
            throw new Exception('La tarjeta ha expirado');
        }
        
        // CVV validation
        if (strlen($data['cvv']) < 3 || strlen($data['cvv']) > 4) {
            throw new Exception('CVV inválido');
        }
    }
    
    /**
     * Get last four digits of card (masked)
     */
    private function getCardLastFour($data) {
        if (isset($data['card_number'])) {
            $cardNumber = preg_replace('/\D/', '', $data['card_number']);
            return substr($cardNumber, -4);
        }
        return null;
    }
    
    /**
     * Detect card brand
     */
    private function getCardBrand($data) {
        if (!isset($data['card_number'])) {
            return null;
        }
        
        $cardNumber = preg_replace('/\D/', '', $data['card_number']);
        $firstDigit = substr($cardNumber, 0, 1);
        $firstTwo = substr($cardNumber, 0, 2);
        
        if ($firstDigit == '4') return 'Visa';
        if (in_array($firstTwo, ['51', '52', '53', '54', '55'])) return 'Mastercard';
        if (in_array($firstTwo, ['34', '37'])) return 'American Express';
        if ($firstTwo == '60') return 'Discover';
        
        return 'Unknown';
    }
    
    /**
     * Extract billing information
     */
    private function getBillingInfo($data) {
        return [
            'name' => $data['cardholder_name'] ?? $data['billing_name'] ?? null,
            'email' => $data['billing_email'] ?? null,
            'address' => $data['billing_address'] ?? null,
            'city' => $data['billing_city'] ?? null,
            'state' => $data['billing_state'] ?? null,
            'zip' => $data['billing_zip'] ?? null,
            'country' => $data['billing_country'] ?? 'US'
        ];
    }
    
    /**
     * Process subscription payment
     */
    public function processSubscriptionPayment($userId, $planName, $paymentData) {
        try {
            // Get plan details
            $plan = getSubscriptionPlan($planName);
            if (!$plan) {
                throw new Exception('Plan de suscripción no encontrado');
            }
            
            // Add plan info to payment data
            $paymentData['amount'] = $plan['price_monthly'];
            $paymentData['user_id'] = $userId;
            $paymentData['subscription_plan'] = $planName;
            
            // Process payment
            $result = $this->processPayment($paymentData);
            
            if ($result['success']) {
                // Update user subscription
                if (updateUserSubscription($userId, $planName, $paymentData['payment_method'])) {
                    // Add payment record to database
                    addPaymentRecord(
                        $userId, 
                        $planName, 
                        $plan['price_monthly'], 
                        $paymentData['payment_method'], 
                        $result['transaction_id']
                    );
                    
                    $result['subscription_updated'] = true;
                } else {
                    $result['subscription_updated'] = false;
                    $result['warning'] = 'Pago procesado pero error al actualizar suscripción';
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate payment receipt
     */
    public function generateReceipt($paymentRecord, $userInfo, $planInfo) {
        return [
            'receipt_id' => 'RCP_' . strtoupper(uniqid()),
            'transaction_id' => $paymentRecord['transaction_id'],
            'date' => $paymentRecord['processed_at'],
            'customer' => [
                'name' => $userInfo['name'],
                'email' => $userInfo['email']
            ],
            'subscription' => [
                'plan' => $planInfo['display_name'],
                'amount' => $paymentRecord['amount'],
                'currency' => $paymentRecord['currency'],
                'billing_period' => 'Monthly'
            ],
            'payment_method' => [
                'type' => $paymentRecord['payment_method'],
                'card_brand' => $paymentRecord['card_brand'],
                'card_last_four' => $paymentRecord['card_last_four']
            ]
        ];
    }
}

/**
 * Helper function to format currency
 */
function formatCurrency($amount, $currency = 'USD') {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'MXN' => '$'
    ];
    
    $symbol = $symbols[$currency] ?? '$';
    return $symbol . number_format($amount, 2);
}

/**
 * Helper function to format card number for display
 */
function formatCardNumber($cardNumber) {
    $cleaned = preg_replace('/\D/', '', $cardNumber);
    $masked = str_repeat('*', strlen($cleaned) - 4) . substr($cleaned, -4);
    return chunk_split($masked, 4, ' ');
}
?>
