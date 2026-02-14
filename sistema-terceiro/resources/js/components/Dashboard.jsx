import React, { useState } from 'react';
import axios from 'axios';
import ThemeToggle from './ThemeToggle';
import ScenarioCard from './ScenarioCard';
import ConsoleOutput from './ConsoleOutput';

const Dashboard = () => {
    const [logs, setLogs] = useState(() => {
        const savedLogs = localStorage.getItem('api_logs');
        return savedLogs ? JSON.parse(savedLogs) : [];
    });
    const [loading, setLoading] = useState(false);

    React.useEffect(() => {
        localStorage.setItem('api_logs', JSON.stringify(logs));
    }, [logs]);

    const scenarios = [
        {
            id: 'happy-path',
            title: 'Caminho Feliz',
            description: 'Envia uma ocorrência válida e espera sucesso (202).',
            endpoint: 'happy-path'
        },
        {
            id: 'idempotency',
            title: 'Idempotência',
            description: 'Envia a mesma ocorrência duas vezes. Espera sucesso em ambas.',
            endpoint: 'idempotency'
        },
        {
            id: 'concurrency',
            title: 'Concorrência',
            description: 'Envia 10 requisições simultâneas. Espera processamento paralelo.',
            endpoint: 'concurrency'
        },
        {
            id: 'update',
            title: 'Atualização',
            description: 'Cria uma ocorrência e depois envia uma atualização para ela.',
            endpoint: 'update'
        }
    ];

    const executeScenario = async (endpoint, title) => {
        setLoading(true);
        addLog(`Iniciando cenário: ${title}...`, { status: 'pending' });

        try {
            const response = await axios.post(`/api/scenarios/${endpoint}`);
            addLog(`Cenário ${title} finalizado com sucesso!`, response.data);
        } catch (error) {
            console.error(error);
            const errorData = error.response ? error.response.data : { message: error.message };
            addLog(`Erro no cenário ${title}`, errorData);
        } finally {
            setLoading(false);
        }
    };

    const addLog = (message, data) => {
        const timestamp = new Date().toLocaleTimeString();
        setLogs(prevLogs => [{ timestamp, message, data }, ...prevLogs]);
    };

    const clearLogs = () => {
        setLogs([]);
        localStorage.removeItem('api_logs');
    };

    return (
        <div className="container">
            <header className="header">
                <div className="title">Sistema Terceiro / API Desafio</div>
                <ThemeToggle />
            </header>

            <div className="grid">
                {scenarios.map(scenario => (
                    <ScenarioCard
                        key={scenario.id}
                        title={scenario.title}
                        description={scenario.description}
                        endpoint={scenario.endpoint}
                        onExecute={executeScenario}
                        isLoading={loading}
                    />
                ))}
            </div>

            <ConsoleOutput logs={logs} onClear={clearLogs} />
        </div>
    );
};

export default Dashboard;
