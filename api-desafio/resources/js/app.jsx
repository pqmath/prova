import './bootstrap';
import '../css/app.css';

import React from 'react';
import { createRoot } from 'react-dom/client';
import Dashboard from './components/Dashboard';

if (document.getElementById('app')) {
    const root = createRoot(document.getElementById('app'));
    root.render(
        <React.StrictMode>
            <Dashboard />
        </React.StrictMode>
    );
}
