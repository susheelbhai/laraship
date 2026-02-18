import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/admin/app-layout';
import RechargeModal from './RechargeModal';
import ProviderDetailsTable from './components/ProviderDetailsTable';
import PickupAddressesSection from './components/PickupAddressesSection';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shipping Providers',
        href: route('admin.shipping_provider.index'),
    },
    {
        title: 'Provider Detail',
        href: '#',
    },
];

export default function Show() {
    const provider = ((usePage<SharedData>().props as any)?.data as {
        id: number;
        name: string;
        display_name: string;
        adapter_class: string;
        is_enabled: boolean;
        priority: number;
        tracking_url_template: string;
        shipments_count: number;
        booking_attempts_count: number;
        wallet_balance: { balance: number; currency: string; formatted: string } | null;
        supports_recharge: boolean;
        recharge_url: string | null;
        created_at: string;
        updated_at: string;
    }) || {};

    const [showRechargeModal, setShowRechargeModal] = useState(false);

    const handleAddMoney = () => {
        if (!provider.supports_recharge && provider.recharge_url) {
            window.open(provider.recharge_url, '_blank');
            return;
        }
        setShowRechargeModal(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Provider Detail" />

            <ProviderDetailsTable provider={provider} onAddMoney={handleAddMoney} />

            {provider.is_enabled && <PickupAddressesSection providerId={provider.id} />}

            <RechargeModal
                isOpen={showRechargeModal}
                onClose={() => setShowRechargeModal(false)}
                providerId={provider.id}
                currency={provider.wallet_balance?.currency}
                rechargeUrl={provider.recharge_url}
            />
        </AppLayout>
    );
}
