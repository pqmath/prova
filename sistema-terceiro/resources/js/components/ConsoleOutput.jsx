import React from 'react';

const ConsoleOutput = ({ logs, onClear }) => {
    return (
        <div className="console-window">
            <div className="console-title" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <span>API Console Output</span>
                <button
                    onClick={onClear}
                    style={{
                        background: 'transparent',
                        border: '1px solid #666',
                        color: '#888',
                        borderRadius: '4px',
                        cursor: 'pointer',
                        fontSize: '0.8rem',
                        padding: '2px 8px'
                    }}
                >
                    Limpar Logs
                </button>
            </div>
            {logs.map((log, index) => (
                <div key={index} style={{ marginBottom: '1rem', borderBottom: '1px solid #444', paddingBottom: '0.5rem' }}>
                    <div style={{ color: '#888', fontSize: '0.8rem' }}>{log.timestamp}</div>
                    <pre style={{ margin: 0 }}>{JSON.stringify(log.data, null, 2)}</pre>
                </div>
            ))}
            {logs.length === 0 && <div style={{ color: '#666' }}>Waiting for scenarios...</div>}
        </div>
    );
};

export default ConsoleOutput;
