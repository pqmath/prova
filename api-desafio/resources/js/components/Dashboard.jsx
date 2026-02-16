import React, { useState, useEffect } from 'react';
import axios from 'axios';
import ThemeToggle from './ThemeToggle';
import AuditLogModal from './AuditLogModal';

function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

const Dashboard = () => {
    const [occurrences, setOccurrences] = useState([]);
    const [selectedOccurrence, setSelectedOccurrence] = useState(null);
    const [loading, setLoading] = useState(false);
    const [apiKey] = useState('bombeiros-api-key-2026');
    const [selectedResource, setSelectedResource] = useState('ABT-01');
    const [isAuditOpen, setIsAuditOpen] = useState(false);

    const fetchOccurrences = async (isBackground = false) => {
        if (!isBackground) setLoading(true);
        try {
            const response = await axios.get('/api/occurrences', {
                headers: { 'X-API-Key': apiKey }
            });
            const data = response.data.data ? response.data.data : response.data;
            setOccurrences(data);
        } catch (error) {
            console.error('Error fetching occurrences:', error);
        } finally {
            if (!isBackground) setLoading(false);
        }
    };

    useEffect(() => {
        fetchOccurrences();
        const interval = setInterval(() => fetchOccurrences(true), 5000);
        return () => clearInterval(interval);
    }, []);

    useEffect(() => {
        if (selectedOccurrence) {
            const updated = occurrences.find(o => o.id === selectedOccurrence.id);
            if (updated) {
                setSelectedOccurrence(updated);
            }
        }
    }, [occurrences, selectedOccurrence?.id]);


    const handleAction = async (id, action, payload = {}) => {
        const idempotencyKey = generateUUID();
        setLoading(true);
        try {
            await axios.post(`/api/occurrences/${id}/${action}`, payload, {
                headers: {
                    'X-API-Key': apiKey,
                    'Idempotency-Key': idempotencyKey
                }
            });
            alert(`Ação ${action} solicitada com sucesso!`);
            fetchOccurrences(false);
        } catch (error) {
            console.error(`Error performing ${action}:`, error);
            alert(`Erro ao executar ${action}: ${error.response?.data?.error || error.message}`);
            setLoading(false);
        }
    };

    // --- VIEWS ---

    const renderList = () => (
        <div className="grid">
            {occurrences.map(occ => (
                <div key={occ.id} className="card">
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                        <span className="badge">{occ.status}</span>
                        <small>{new Date(occ.reported_at).toLocaleString()}</small>
                    </div>
                    <h3>{occ.type}</h3>
                    <p>{occ.description}</p>
                    <button className="btn" onClick={() => setSelectedOccurrence(occ)} style={{ marginTop: 'auto', width: '100%' }}>
                        Ver Detalhes
                    </button>
                </div>
            ))}
            {occurrences.length === 0 && !loading && <p>Nenhuma ocorrência encontrada.</p>}
        </div>
    );

    const renderDetail = () => {
        const occ = selectedOccurrence;
        return (
            <div>
                <button className="btn" onClick={() => setSelectedOccurrence(null)} style={{ marginBottom: '1rem', width: 'auto' }}>
                    &larr; Voltar para Lista
                </button>

                <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: '2rem', alignItems: 'start' }}>
                    {/* LEFT COLUMN: Main Info & History */}
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '2rem' }}>
                        <div className="card" style={{ cursor: 'default', transform: 'none', height: 'fit-content' }}>
                            <div className="header" style={{ marginBottom: '1rem' }}>
                                <div>
                                    <h2>{occ.type}</h2>
                                    <small>ID Interno: {occ.id}</small><br />
                                    <small>ID Externo: {occ.external_id}</small>
                                </div>
                                <span className="badge" style={{ fontSize: '1.2rem' }}>{occ.status}</span>
                            </div>

                            <div style={{ marginBottom: '1rem' }}>
                                <h4>Descrição</h4>
                                <p>{occ.description}</p>
                                <p><strong>Reportado em:</strong> {new Date(occ.reported_at).toLocaleString()}</p>
                            </div>
                        </div>

                        <div className="card" style={{ cursor: 'default', transform: 'none', height: 'fit-content' }}>
                            <h4>Histórico de Despachos</h4>
                            {occ.dispatches && occ.dispatches.length > 0 ? (
                                <table style={{ width: '100%', textAlign: 'left', borderCollapse: 'collapse' }}>
                                    <thead>
                                        <tr style={{ borderBottom: '1px solid var(--border-color)' }}>
                                            <th style={{ padding: '0.5rem' }}>Recurso</th>
                                            <th style={{ padding: '0.5rem' }}>Status</th>
                                            <th style={{ padding: '0.5rem' }}>Atualizado em</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {occ.dispatches.map(d => (
                                            <tr key={d.id} style={{ borderBottom: '1px solid var(--border-color)' }}>
                                                <td style={{ padding: '0.5rem' }}>{d.resource_code}</td>
                                                <td style={{ padding: '0.5rem' }}>{d.status}</td>
                                                <td style={{ padding: '0.5rem' }}>{new Date(d.updated_at).toLocaleString()}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            ) : (
                                <p>Nenhum despacho registrado.</p>
                            )}
                        </div>
                    </div>

                    {/* RIGHT COLUMN: Actions */}
                    <div>
                        <div className="card" style={{ cursor: 'default', transform: 'none', position: 'sticky', top: '2rem', height: 'fit-content' }}>
                            <h4 style={{ marginTop: 0 }}>Ações Disponíveis</h4>

                            {occ.status === 'resolved' ? (
                                <div style={{
                                    padding: '1rem',
                                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                                    color: 'var(--success-color)',
                                    borderRadius: '0.25rem',
                                    border: '1px solid var(--success-color)',
                                    textAlign: 'center'
                                }}>
                                    ✓ Ocorrência resolvida e encerrada
                                </div>
                            ) : (
                                <div className="actions" style={{ flexDirection: 'column' }}>
                                    {occ.status === 'reported' && (
                                        <button className="btn" onClick={() => handleAction(occ.id, 'start')} style={{ width: '100%' }}>
                                            Iniciar Atendimento
                                        </button>
                                    )}

                                    {occ.status === 'in_progress' && (
                                        <>
                                            <div style={{ marginBottom: '0.5rem' }}>
                                                <label style={{ display: 'block', marginBottom: '0.25rem', fontSize: '0.9rem' }}>Selecione o Recurso:</label>
                                                <select
                                                    value={selectedResource}
                                                    onChange={(e) => setSelectedResource(e.target.value)}
                                                    style={{
                                                        width: '100%',
                                                        padding: '0.5rem',
                                                        backgroundColor: 'var(--bg-color)',
                                                        color: 'var(--text-color)',
                                                        border: '1px solid var(--border-color)',
                                                        borderRadius: '0.25rem'
                                                    }}
                                                >
                                                    <option value="ABT-01">ABT-01 (Auto Bomba Tanque)</option>
                                                    <option value="ABT-02">ABT-02 (Auto Bomba Tanque)</option>
                                                    <option value="ASU-01">ASU-01 (Auto Resgate)</option>
                                                    <option value="ABS-01">ABS-01 (Auto Busca e Salvamento)</option>
                                                    <option value="AR-01">AR-01 (Auto Rápido)</option>
                                                </select>
                                            </div>
                                            <button className="btn" onClick={() => handleAction(occ.id, 'dispatches', { resourceCode: selectedResource })} style={{ width: '100%' }}>
                                                Despachar Viatura
                                            </button>
                                            <hr style={{ width: '100%', borderColor: 'var(--border-color)', margin: '1rem 0' }} />
                                            <button className="btn" onClick={() => handleAction(occ.id, 'resolve')} style={{ width: '100%', backgroundColor: '#28a745', border: '1px solid #1e7e34', fontWeight: 'bold' }}>
                                                Resolver Ocorrência
                                            </button>
                                        </>
                                    )}
                                </div>
                            )}

                            {occ.status !== 'resolved' && occ.status !== 'reported' && occ.status !== 'in_progress' && (
                                <p>Nenhuma ação disponível para o status atual.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <div className="container">
            <header className="header">
                <div>
                    <div className="title">API Desafio</div>
                    <small>Sistema para gerenciar ocorrências</small>
                </div>
                <div style={{ display: 'flex', gap: '1rem', alignItems: 'center' }}>
                    <button className="btn" onClick={() => setIsAuditOpen(true)}>Auditoria</button>
                    <ThemeToggle />
                </div>
            </header>

            {selectedOccurrence ? renderDetail() : renderList()}

            <AuditLogModal isOpen={isAuditOpen} onClose={() => setIsAuditOpen(false)} apiKey={apiKey} />

            {loading && (
                <div style={{
                    position: 'fixed',
                    bottom: '20px',
                    right: '20px',
                    backgroundColor: 'var(--card-bg)',
                    padding: '8px 12px',
                    borderRadius: '20px',
                    boxShadow: '0 2px 10px rgba(0,0,0,0.3)',
                    border: '1px solid var(--border-color)',
                    zIndex: 9999,
                    color: 'var(--text-color)',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    fontSize: '0.8rem'
                }}>
                    <div className="spinner" style={{
                        width: '14px',
                        height: '14px',
                        border: '2px solid rgba(255,255,255,0.3)',
                        borderRadius: '50%',
                        borderTopColor: 'var(--primary-color)',
                        animation: 'spin 1s ease-in-out infinite'
                    }}></div>
                    <span>Sincronizando...</span>
                    <style>{`
                        @keyframes spin {
                            to { transform: rotate(360deg); }
                        }
                    `}</style>
                </div>
            )}
        </div>
    );
};

export default Dashboard;
