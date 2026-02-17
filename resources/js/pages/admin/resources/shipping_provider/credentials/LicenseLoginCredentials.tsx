import { InputDiv } from '@/components/form/container/input-div';
import type { InputDivData } from '@/lib/use-form-handler';

interface Props {
    inputDivData: InputDivData;
}

export function LicenseLoginCredentials({ inputDivData }: Props) {
    return (
        <>
            <InputDiv
                type="text"
                label="License Key"
                name="credentials_license_key"
                inputDivData={inputDivData}
                placeholder="Enter License Key"
            />
            <InputDiv
                type="text"
                label="Login ID"
                name="credentials_login_id"
                inputDivData={inputDivData}
                placeholder="Enter Login ID"
            />
        </>
    );
}
