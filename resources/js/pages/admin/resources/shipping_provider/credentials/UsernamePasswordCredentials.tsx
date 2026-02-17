import { InputDiv } from '@/components/form/container/input-div';
import type { InputDivData } from '@/lib/use-form-handler';

interface Props {
    inputDivData: InputDivData;
}

export function UsernamePasswordCredentials({ inputDivData }: Props) {
    return (
        <>
            <InputDiv
                type="text"
                label="Username"
                name="credentials_username"
                inputDivData={inputDivData}
                placeholder="Enter username"
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
