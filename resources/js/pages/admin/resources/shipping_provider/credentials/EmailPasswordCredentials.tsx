import { InputDiv } from '@/components/form/container/input-div';
import type { InputDivData } from '@/lib/use-form-handler';

interface Props {
    inputDivData: InputDivData;
}

export function EmailPasswordCredentials({ inputDivData }: Props) {
    return (
        <>
            <InputDiv
                type="email"
                label="Email"
                name="credentials_email"
                inputDivData={inputDivData}
                placeholder="Enter email address"
            />
            <InputDiv
                type="password"
                label="Password"
                name="credentials_password"
                inputDivData={inputDivData}
                placeholder="Enter password"
            />
        </>
    );
}
