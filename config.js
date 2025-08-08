const roleConfigs = {
    admin: {
        title: 'admin Dashboard',
        badge: 'Administrator',
        tabs: [
            { id: 'dashboard', label: 'Dashboard', content: 'admin-dashboard' },
            { id: 'attendance', label: 'Attendance', content: 'admin-attendance' },
            { id: 'absence', label: 'Absence', content: 'admin-absence' },
            { id: 'report', label: 'Report', content: 'admin-report' },
            { id: 'lecturer', label: 'Lecturer', content: 'admin-lecturer' },
            { id: 'student', label: 'Student', content: 'admin-student' },
            { id: 'subject', label: 'Subject', content: 'admin-subject' },
            { id: 'logout', label: 'Logout', content: null, isLogout: true }
        ]
    }
}