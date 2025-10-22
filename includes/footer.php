    </main>
    <footer>
        <p>&copy; <?= date('Y') ?> <strong>Hardyfloor Group.</strong> Todos os direitos reservados.</p>
        Developed by: Roberto Garbini
    </footer>

    <!-- Importa a biblioteca Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Função para gerar cores aleatórias para os gráficos
        const generateColors = (numColors) => {
            const colors = [];
            for (let i = 0; i < numColors; i++) {
                const r = Math.floor(Math.random() * 200);
                const g = Math.floor(Math.random() * 200);
                const b = Math.floor(Math.random() * 200);
                colors.push(`rgb(${r}, ${g}, ${b})`);
            }
            return colors;
        };
        
        // GRÁFICO DE RECEITAS
        const receitasLabels = <?= $receitas_labels ?? '[]' ?>;
        if (receitasLabels.length > 0) {
            const receitasCtx = document.getElementById('receitasChart');
            new Chart(receitasCtx, {
                type: 'doughnut', // 'die' ou 'doughnut' para um gráfico com um buraco no meio
                data: {
                    labels: receitasLabels,
                    datasets: [{
                        label: 'Receitas',
                        data: <?= $receitas_data ?? '[]' ?>,
                        backgroundColor: generateColors(receitasLabels.length),
                        hoverOffset: 4
                    }]
                }
            });
        }
        
        // GRÁFICO DE DESPESAS
        const despesasLabels = <?= $despesas_labels ?? '[]' ?>;
        if (despesasLabels.length > 0) {
            const despesasCtx = document.getElementById('despesasChart');
            new Chart(despesasCtx, {
                type: 'doughnut', // 'die' ou 'doughnut' para um gráfico com um buraco no meio
                data: {
                    labels: despesasLabels,
                    datasets: [{
                        label: 'Despesas',
                        data: <?= $despesas_data ?? '[]' ?>,
                        backgroundColor: generateColors(despesasLabels.length),
                        hoverOffset: 4
                    }]
                }
            });
        }
        
    </script>
    
    <!-- **** SCRIPT PARA FECHAR NOTIFICAÇÕES AUTOMATICAMENTE **** -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const notification = document.querySelector('.notification');
            if (notification) {
                // Espera 3 segundos
                setTimeout(() => {
                    notification.classList.add('fade-out');
                    // Espera a animação terminar para remover o elemento
                    setTimeout(() => {
                        notification.remove();
                    }, 500); // 0.5 segundos (mesmo tempo da transição CSS)
                }, 3000); // 3000ms = 3 segundos
            }
        });
    </script>
    
    <!-- **** SCRIPT PARA A MODAL DE PAGAMENTO **** -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('paymentModal');
        const closeBtn = document.querySelector('.close-button');
        const paymentForm = document.getElementById('paymentForm');
        const lancamentoIdInput = document.getElementById('lancamentoId');
        
        // Abre a modal ao clicar em qualquer botão de pagar
        document.querySelectorAll('.open-modal-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                lancamentoIdInput.value = id; // Define o ID no formulário da modal
                modal.style.display = 'block';
            });
        });
        
        // Fecha a modal ao clicar no 'X'
        if (closeBtn) {
            closeBtn.onclick = () => {
                modal.style.display = 'none';
            }
        }
        
        // Fecha a modal ao clicar fora dela
        window.onclick = (event) => {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    });
</script>

<!-- **** SCRIPT PARA MENUS DROPDOWN NO MOBILE **** -->
    <script>
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', (event) => {
                // Previne o comportamento padrão do link (#) apenas em telas pequenas
                if (window.innerWidth <= 768) { 
                    event.preventDefault();
                    // Fecha outros dropdowns abertos
                    document.querySelectorAll('.dropdown-menu.is-open').forEach(openMenu => {
                        if (openMenu !== toggle.nextElementSibling) {
                            openMenu.classList.remove('is-open');
                        }
                    });
                    // Abre ou fecha o dropdown atual
                    toggle.nextElementSibling.classList.toggle('is-open');
                }
            });
        });

        // Fecha os dropdowns se clicar fora deles
        window.addEventListener('click', (event) => {
            if (!event.target.matches('.dropdown-toggle')) {
                document.querySelectorAll('.dropdown-menu.is-open').forEach(openMenu => {
                    openMenu.classList.remove('is-open');
                });
            }
        });
    </script>
    
    <!-- **** SCRIPT PARA A MODAL DE ANEXO **** -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const anexoModal = document.getElementById('anexoModal');
    if (!anexoModal) return; // Para o script se a modal não existir na página

    const closeAnexoBtn = anexoModal.querySelector('.modal-close-btn');
    const anexoLancamentoIdInput = anexoModal.querySelector('#anexoLancamentoId');
    const anexoDisplay = anexoModal.querySelector('#anexo-display');
    
    document.querySelectorAll('.anexo-btn').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-id');
            const comprovante = button.getAttribute('data-comprovante');
            
            anexoLancamentoIdInput.value = id;
            anexoDisplay.innerHTML = ''; // Limpa o display anterior

            if (comprovante) {
                const filePath = `uploads/comprovantes/${comprovante}`;
                let previewHtml = '';
                
                if (/\.(pdf)$/i.test(comprovante)) {
                    previewHtml = `<div class="file-icon"><i class="fas fa-file-pdf"></i></div>`;
                } else if (/\.(jpe?g|png|gif)$/i.test(comprovante)) {
                    previewHtml = `<div class="anexo-image-preview"><img src="${filePath}" alt="Comprovante"></div>`;
                }

               anexoDisplay.innerHTML = `
                    ${previewHtml}
                    <p class="file-name">${comprovante.substring(comprovante.indexOf('_') + 1)}</p>
                    <div class="anexo-actions">
                        <!-- ÍCONES-BOTÃO -->
                        <a href="${filePath}" download class="icon-btn download-icon" title="Baixar Comprovante">
                            <i class="fas fa-download"></i>
                        </a>
                        <a href="${filePath}" target="_blank" class="icon-btn view-icon" title="Imprimir/Ver Comprovante">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="excluir_comprovante.php?id=${id}" class="icon-btn delete-icon" title="Excluir Comprovante" onclick="return confirm('Tem certeza que deseja excluir este comprovante?')">
                            <i class="fas fa-trash-can"></i>
                        </a>
                    </div>
                `;
            } else {
                 anexoDisplay.innerHTML = '<p style="text-align:center; color: #777;">Nenhum comprovante anexado.</p>';
            }
            
            anexoModal.style.display = 'block';
        });
    });
    
    if (closeAnexoBtn) { closeAnexoBtn.onclick = () => anexoModal.style.display = 'none'; }
    window.addEventListener('click', (event) => {
        if (event.target == anexoModal) { anexoModal.style.display = 'none'; }
    });
});
</script>
</body>
</html>