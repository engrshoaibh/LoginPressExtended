import { Notice } from '@wordpress/components';

/**
 * Notice Message Component
 */
const NoticeMessage = ({ notice, onDismiss }) => {
    if (!notice) {
        return null;
    }

    return (
        <Notice
            status={notice.type}
            isDismissible
            onRemove={onDismiss}
        >
            {notice.message}
        </Notice>
    );
};

export default NoticeMessage;

