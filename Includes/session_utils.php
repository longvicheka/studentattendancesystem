<?php
/**
 * Session Utilities for Student Attendance System
 * Centralized functions for session management
 */

/**
 * Get user full name from session
 * @return string User's full name
 */
function get_user_fullname() {
    if (isset($_SESSION['adminName']) && !empty($_SESSION['adminName'])) {
        return $_SESSION['adminName'];
    } elseif (isset($_SESSION['firstName']) && isset($_SESSION['lastName'])) {
        return $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];
    }
    return 'User';
}

/**
 * Get user type from session
 * @return string User type (Administrator/Student/Guest)
 */
function get_user_type() {
    return isset($_SESSION['userType']) ? $_SESSION['userType'] : 'Guest';
}

/**
 * Check if user is administrator
 * @return bool True if user is administrator
 */
function is_administrator() {
    return isset($_SESSION['userType']) && $_SESSION['userType'] === 'Administrator';
}

/**
 * Check if user is student
 * @return bool True if user is student
 */
function is_student() {
    return isset($_SESSION['userType']) && $_SESSION['userType'] === 'Student';
}

