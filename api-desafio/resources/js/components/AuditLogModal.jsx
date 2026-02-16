import React, { useState, useEffect } from 'react';
import axios from 'axios';

const AuditLogModal = ({ isOpen, onClose, apiKey }) => {
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);

    const fetchLogs = async (pageNum = 1) => {
        setLoading(true);
        try {
            const response = await axios.get(`/api/audit-logs?page=${pageNum}`, {
                headers: { 'X-API-Key': apiKey }
            });
            setLogs(response.data.data);
            setPage(response.data.current_page);
            setLastPage(response.data.last_page);
        } catch (error) {
            console.error('Error fetching audit logs:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (isOpen) {
            fetchLogs();
        }
    }, [isOpen]);

    if (!isOpen) return null;

    return (
        <div style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            backgroundColor: 'rgba(0,0,0,0.7)',
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
            zIndex: 10000
        }}>
            <div className="card" style={{
                width: '90%',
                maxWidth: '800px',
                maxHeight: '90vh',
                overflow: 'hidden',
                display: 'flex',
                flexDirection: 'column',
                padding: '0'
            }}>
                <div className="header" style={{ padding: '1rem', borderBottom: '1px solid var(--border-color)', margin: 0, justifyContent: 'space-between' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                        <span>🔍</span>
                        <h3>Audit Log</h3>
                    </div>
                    <button className="btn" onClick={onClose} style={{ flex: 'none', backgroundColor: 'var(--danger-color)', padding: '0.25rem 0.5rem' }}>X</button>
                </div>

                <div style={{ padding: '1rem', overflowY: 'auto', flex: 1 }}>
                    {loading ? (
                        <p style={{ textAlign: 'center' }}>Carregando auditoria...</p>
                    ) : (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                            {logs.map(log => (
                                <div key={log.id} style={{
                                    border: '1px solid var(--border-color)',
                                    borderRadius: '0.25rem',
                                    padding: '0.5rem',
                                    fontSize: '0.9rem'
                                }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                                        <strong>
                                            {log.entity_type === 'Occurrence' ? '📋 Occurrence' : (log.entity_type === 'Dispatch' ? '🚒 Dispatch' : log.entity_type)}
                                            • {log.entity_id}
                                        </strong>
                                        <small>{new Date(log.created_at).toLocaleString()}</small>
                                    </div>
                                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem' }}>
                                        <div>
                                            Ação: <span className="badge">{log.action}</span>
                                        </div>
                                        <div>
                                            Por: <strong>{log.source || 'Sistema'}</strong>
                                        </div>

                                        {/* Status Change Key Diff */}
                                        {log.action === 'status_changed' && log.before?.status && log.after?.status && (
                                            <div style={{ gridColumn: '1 / -1', marginTop: '0.5rem', backgroundColor: 'rgba(255,255,255,0.05)', padding: '0.5rem', borderRadius: '4px' }}>
                                                De: <span style={{ color: 'var(--danger-color)' }}>{log.before.status}</span>
                                                {' '} → {' '}
                                                Para: <span style={{ color: 'var(--success-color)' }}>{log.after.status}</span>
                                            </div>
                                        )}

                                        {/* Generic Diff for other fields if needed, or specific actions */}
                                        {(log.action === 'started' || log.action === 'resolved') && log.after?.status && (
                                            <div style={{ gridColumn: '1 / -1', marginTop: '0.5rem', backgroundColor: 'rgba(255,255,255,0.05)', padding: '0.5rem', borderRadius: '4px' }}>
                                                Status: <span style={{ color: 'var(--success-color)' }}>{log.after.status}</span>
                                            </div>
                                        )}

                                        {log.action === 'created' && (
                                            <div style={{ gridColumn: '1 / -1', marginTop: '0.5rem', fontStyle: 'italic' }}>
                                                Novo registro criado.
                                            </div>
                                        )}
                                    </div>
                                    <div style={{ marginTop: '0.5rem', fontSize: '0.8rem', opacity: 0.7 }}>
                                        Metadados: {JSON.stringify(log.meta)}
                                    </div>
                                </div>
                            ))}
                            {logs.length === 0 && <p>Nenhum registro encontrado.</p>}
                        </div>
                    )}
                </div>

                <div style={{ padding: '1rem', borderTop: '1px solid var(--border-color)', display: 'flex', justifyContent: 'center', gap: '1rem' }}>
                    <button className="btn" disabled={page <= 1} onClick={() => fetchLogs(page - 1)}>Anterior</button>
                    <span style={{ alignSelf: 'center' }}>Página {page} de {lastPage}</span>
                    <button className="btn" disabled={page >= lastPage} onClick={() => fetchLogs(page + 1)}>Próxima</button>
                </div>
            </div>
        </div>
    );
};

export default AuditLogModal;
