/**
 * Task Icon Component
 */
const TaskIcon = ({ type }) => {
    const icons = {
        history: '🔄',
        reminder: '📧'
    };

    return (
        <span className={`lp-task-icon lp-task-icon-${type}`}>
            {icons[type]}
        </span>
    );
};

export default TaskIcon;

