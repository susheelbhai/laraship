import type { InputDivData } from '@/lib/use-form-handler';
import { ApiKeyCredentials } from './ApiKeyCredentials';
import { ApiKeySecretCredentials } from './ApiKeySecretCredentials';
import { EmailPasswordCredentials } from './EmailPasswordCredentials';
import { UsernamePasswordCredentials } from './UsernamePasswordCredentials';
import { LicenseLoginCredentials } from './LicenseLoginCredentials';

interface Props {
    adapterClass: string;
    inputDivData: InputDivData;
}

export function CredentialFields({ adapterClass, inputDivData }: Props) {
    // Shiprocket uses email/password
    if (adapterClass.includes('ShiprocketAdapter')) {
        return <EmailPasswordCredentials inputDivData={inputDivData} />;
    }

    // EcomExpress uses username/password
    if (adapterClass.includes('EcomExpressAdapter')) {
        return <UsernamePasswordCredentials inputDivData={inputDivData} />;
    }

    // Bluedart uses license_key/login_id
    if (adapterClass.includes('BluedartAdapter')) {
        return <LicenseLoginCredentials inputDivData={inputDivData} />;
    }

    // FedEx, DHL use api_key/secret_key
    if (adapterClass.includes('FedexAdapter') || adapterClass.includes('DhlAdapter')) {
        return <ApiKeySecretCredentials inputDivData={inputDivData} />;
    }

    // Default: Most providers use just api_key
    // (Delhivery, DTDC, Pickrr, Shadowfax, etc.)
    return <ApiKeyCredentials inputDivData={inputDivData} />;
}
