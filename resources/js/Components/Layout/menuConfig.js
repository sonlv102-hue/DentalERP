export const menuConfig = [
    {
        label: 'TỔNG QUAN',
        items: [
            { label: 'Tổng quan', route: 'dashboard', icon: 'home' }
        ]
    },
    {
        label: 'CRM & KHÁCH HÀNG',
        items: [
            { label: 'Khách hàng', route: 'patients.index', icon: 'users', permission: 'patients.view' },
        ]
    },
    {
        label: 'LỊCH HẸN',
        items: [
            { label: 'Đăng ký khám', route: 'schedule.registrations.index', icon: 'appointment', permission: 'appointments.view' },
            { label: 'Lịch hẹn', route: 'schedule.appointments.index', icon: 'appointment', permission: 'appointments.view' }
        ]
    },
    {
        label: 'ĐIỀU TRỊ',
        items: [
            { label: 'Kế hoạch điều trị', route: 'clinical.treatment-plans.index', icon: 'treatment-plan', permission: 'treatment_plans.view' },
        ]
    },
    {
        label: 'THU NGÂN',
        items: [
            { label: 'Hóa đơn', route: 'cashier.invoices.index', icon: 'invoice', permission: 'cashier.view' },
            { label: 'Công nợ', route: 'cashier.debts.index', icon: 'debt', permission: 'cashier.view' },
        ]
    },
];
