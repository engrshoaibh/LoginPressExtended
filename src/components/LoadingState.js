import { Spinner } from '@wordpress/components';

/**
 * Loading State Component
 */
const LoadingState = () => {
    return (
        <div className="lp-task-loading">
            <Spinner />
            <p>Loading settings...</p>
        </div>
    );
};

export default LoadingState;

