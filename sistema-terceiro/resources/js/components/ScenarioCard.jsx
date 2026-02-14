import React from 'react';

const ScenarioCard = ({ title, description, endpoint, onExecute, isLoading }) => {
    return (
        <div className="card">
            <h3>{title}</h3>
            <p>{description}</p>
            <button
                className="btn"
                onClick={() => onExecute(endpoint, title)}
                disabled={isLoading}
            >
                {isLoading ? 'Aguarde...' : 'Executar Cenário'}
            </button>
        </div>
    );
};

export default ScenarioCard;
