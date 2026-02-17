import { InputDiv } from '@/components/form/container/input-div';
import type { InputDivData } from '@/lib/use-form-handler';

interface Props {
    inputDivData: InputDivData;
}

export function ApiKeySecretCredentials({ inputDivData }: Props) {
    return (
        <>
            <InputDiv
                type="text"
                label="API Key"
                name="credentials_api_key"
                inputDivData={inputDivData}
                placeholder="Enter API Key"
            />
            <InputDiv
                type="text"
                label="Secret Key"
                name="credentials_secret_key"
                inputDivData={inputDivData}
                placeholder="Enter Secret Key"
            />
        </>
    );
}
