(() => {

    const buttons = document.querySelectorAll('.fs-player-btn');
    const output = document.getElementById('fs-player-recommendation');

    if (!buttons.length || !output) return;

    const recommendations = {
        explosivo: `
            <strong>Jugador explosivo:</strong><br>
            Recomendamos ajuste ceñido. Considera media talla menos
            si prefieres mayor precisión en cambios rápidos.
        `,
        tecnico: `
            <strong>Jugador técnico:</strong><br>
            Ajuste preciso y horma estrecha.
            Modelos tipo Nike o Mizuno suelen funcionar mejor.
        `,
        defensivo: `
            <strong>Jugador defensivo:</strong><br>
            Prioriza estabilidad y confort.
            Ajuste estándar o ligeramente más amplio.
        `
    };

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {

            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const type = btn.dataset.player;
            output.innerHTML = recommendations[type] || '';
        });
    });

})();
