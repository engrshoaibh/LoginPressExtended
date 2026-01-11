import { render } from '@wordpress/element';
import App from './components/App';

// Mount the React app
const rootElement = document.getElementById('loginpress-task-root');

if (rootElement) {
    render(<App />, rootElement);
}

