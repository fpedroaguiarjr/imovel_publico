// Funções Globais da Aplicação

// Função para filtrar por status na página inicial
function filtrarStatus(status) {
    window.location.href = `index.php?status=${encodeURIComponent(status)}`;
}

// Função para pesquisar na página inicial
function pesquisar() {
    const searchTerm = document.getElementById("search").value;
    if (searchTerm.trim()) {
        window.location.href = `index.php?search=${encodeURIComponent(searchTerm.trim())}`;
    } else {
        window.location.href = "index.php"; // Volta para a página inicial sem pesquisa
    }
}

// Função para redirecionar para a página de alteração (histórico)
function alterarProcesso() {
    const processo = prompt("Digite o número do processo que deseja alterar:");
    // Valida se o valor digitado é um número positivo
    if (processo && /^[1-9]\d*$/.test(processo)) {
        window.location.href = `historico.php?processo=${processo}`;
    } else if (processo !== null) { // Se o usuário não cancelou, mas digitou algo inválido
        alert("Por favor, digite um número de processo válido.");
    }
}

// Adiciona listener para pesquisar com Enter na página inicial
const searchInput = document.getElementById("search");
if (searchInput) {
    searchInput.addEventListener("keyup", function (e) {
        if (e.key === "Enter") {
            pesquisar();
        }
    });
}

// Funções e validações específicas para a página de histórico
if (document.querySelector(".historico-section")) {
    
    document.addEventListener("DOMContentLoaded", function () {
        const form = document.querySelector("form#historicoForm"); // Adicionado ID ao form para seletor mais específico
        const errorContainer = document.getElementById("error-messages") || createErrorContainer(form);

        // 1. Configuração de Uploads de Arquivo (Preview e Validação)
        setupFileUploads(form);

        // 2. Validação do Formulário no Submit
        if (form) {
            form.addEventListener("submit", function (e) {
                let isValid = true;
                const errorMessages = [];
                errorContainer.innerHTML = ""; // Limpa erros anteriores
                errorContainer.style.display = "none";

                // Limpa classes de erro anteriores
                form.querySelectorAll(".error").forEach(el => el.classList.remove("error"));

                // Validação: Pelo menos um campo de texto ou upload deve ser preenchido/enviado
                const hasContent = 
                    form.querySelector("[name=\"texto_historico\"]")?.value.trim() ||
                    form.querySelector("[name=\"texto_documentos\"]")?.value.trim() ||
                    form.querySelector("[name=\"texto_averbacoes\"]")?.value.trim() ||
                    form.querySelector("[name=\"texto_rgi\"]")?.value.trim() ||
                    form.querySelector("[name=\"image_data_maps\"]")?.value.trim() || // Verifica imagem colada Maps
                    form.querySelector("[name=\"image_data_earth\"]")?.value.trim() || // Verifica imagem colada Earth
                    Array.from(form.querySelectorAll("input[type=\"file\"]")).some(input => input.files.length > 0);

                if (!hasContent) {
                    isValid = false;
                    errorMessages.push("É necessário preencher pelo menos um campo de texto, colar uma imagem de localização ou anexar um arquivo.");
                }

                // Validação de tamanho e tipo dos arquivos (já feita no change, mas reforça no submit)
                form.querySelectorAll("input[type=\"file\"]").forEach(input => {
                    Array.from(input.files).forEach(file => {
                        const allowedTypes = [
                            "image/jpeg", "image/png", "image/gif", "application/pdf", 
                            "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                            "image/jpg" // Adicionado jpg explicitamente se necessário
                        ];
                        const maxSize = 5 * 1024 * 1024; // 5MB

                        if (file.size > maxSize) {
                            isValid = false;
                            input.classList.add("error");
                            errorMessages.push(`O arquivo "${file.name}" excede o tamanho máximo de 5MB.`);
                        }
                        if (!allowedTypes.includes(file.type)) {
                            // Tenta verificar pelo nome se o tipo está vazio (menos confiável)
                            const extension = file.name.split(".").pop().toLowerCase();
                            const mimeFromExt = {
                                "jpg": "image/jpeg", "jpeg": "image/jpeg", "png": "image/png",
                                "gif": "image/gif", "pdf": "application/pdf", "doc": "application/msword",
                                "docx": "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            };
                            if (!file.type && allowedTypes.includes(mimeFromExt[extension])) {
                                // Permite se a extensão for conhecida e o tipo vazio
                            } else if (!allowedTypes.includes(file.type)) {
                                isValid = false;
                                input.classList.add("error");
                                errorMessages.push(`O tipo de arquivo "${file.name}" (${file.type || extension}) não é permitido.`);
                            }
                        }
                    });
                });

                if (!isValid) {
                    e.preventDefault(); // Impede o envio do formulário
                    displayErrors(errorMessages, errorContainer, form);
                }
            });
        }

        // Drag and drop para screenshots
    const screenshotAreas = document.querySelectorAll('.screenshot-area');
    
    screenshotAreas.forEach(function(area) {
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#e9e9e9';
        });

        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#f9f9f9';
        });

        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#f9f9f9';
            
            const file = e.dataTransfer.files[0];
            if (file.type.match('image.*')) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '100%';
                    
                    // Limpa a área antes de adicionar nova imagem
                    while (area.firstChild) {
                        area.removeChild(area.firstChild);
                    }
                    
                    area.appendChild(img);
                    
                    // Cria um input hidden para enviar a imagem
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = area.id === 'screenshot1-area' ? 'screenshot1' : 'screenshot2';
                    input.value = e.target.result;
                    area.appendChild(input);
                };
                
                reader.readAsDataURL(file);
            }
        });
    });

    // Botão para colar screenshot
    document.querySelectorAll('.btn-colar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const areaId = this.getAttribute('data-target');
            const area = document.getElementById(areaId);
            
            navigator.clipboard.read().then(clipboardItems => {
                for (const clipboardItem of clipboardItems) {
                    for (const type of clipboardItem.types) {
                        if (type.startsWith('image/')) {
                            clipboardItem.getType(type).then(blob => {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    const img = document.createElement('img');
                                    img.src = e.target.result;
                                    img.style.maxWidth = '100%';
                                    
                                    // Limpa a área antes de adicionar nova imagem
                                    while (area.firstChild) {
                                        area.removeChild(area.firstChild);
                                    }
                                    
                                    area.appendChild(img);
                                    
                                    // Cria um input hidden para enviar a imagem
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = areaId === 'screenshot1-area' ? 'screenshot1' : 'screenshot2';
                                    input.value = e.target.result;
                                    area.appendChild(input);
                                };
                                reader.readAsDataURL(blob);
                            });
                        }
                    }
                }
            }).catch(err => {
                console.error('Erro ao acessar clipboard:', err);
                alert('Não foi possível acessar a área de transferência. Certifique-se de que você copiou uma imagem.');
            });
        });
    });

        // 3. Rolagem Suave para Âncoras do Menu
        document.querySelectorAll(".historico-menu a").forEach((anchor) => {
            anchor.addEventListener("click", function (e) {
                e.preventDefault();
                const targetId = this.getAttribute("href");
                const targetElement = document.querySelector(targetId);

                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: "smooth",
                        block: "start", // Alinha ao topo da seção
                    });
                }
            });
        });
    }); // Fim do DOMContentLoaded

    /**
     * Configura os inputs de arquivo para mostrar preview e validar no change.
     * @param {HTMLFormElement} form O formulário que contém os inputs.
     */
    function setupFileUploads(form) {
        form.querySelectorAll("input[type=\"file\"]").forEach((input) => {
            const previewContainer = input.closest(".file-upload-container")?.querySelector(".image-preview"); // Procura preview dentro do container
            
            input.addEventListener("change", function (e) {
                clearUploadError(this); // Limpa erros anteriores deste input
                if (previewContainer) previewContainer.innerHTML = ""; // Limpa preview anterior

                if (this.files.length > 0) {
                    const file = this.files[0];
                    const allowedTypes = [
                        "image/jpeg", "image/png", "image/gif", "application/pdf", 
                        "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                        "image/jpg"
                    ];
                    const maxSize = 5 * 1024 * 1024; // 5MB

                    // Validar tamanho
                    if (file.size > maxSize) {
                        showUploadError(this, `Arquivo "${file.name}" muito grande. Máximo: 5MB.`);
                        this.value = ""; // Limpa a seleção
                        return;
                    }

                    // Validar tipo
                    if (!allowedTypes.includes(file.type)) {
                         // Tenta verificar pelo nome se o tipo está vazio
                         const extension = file.name.split(".").pop().toLowerCase();
                         const mimeFromExt = {
                             "jpg": "image/jpeg", "jpeg": "image/jpeg", "png": "image/png",
                             "gif": "image/gif", "pdf": "application/pdf", "doc": "application/msword",
                             "docx": "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                         };
                         if (!file.type && allowedTypes.includes(mimeFromExt[extension])) {
                             // Permite
                         } else {
                            showUploadError(this, `Tipo de arquivo "${file.name}" (${file.type || extension}) não permitido.`);
                            this.value = ""; // Limpa a seleção
                            return;
                         }
                    }

                    // Mostrar preview se for imagem e houver container
                    if (previewContainer && file.type.startsWith("image/")) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview de ${file.name}">`;
                        };
                        reader.readAsDataURL(file);
                    } else if (previewContainer) {
                        // Mostrar ícone ou nome para outros tipos
                        previewContainer.innerHTML = `<span>${file.name}</span>`;
                    }
                }
            });
        });
    }

    /**
     * Mostra uma mensagem de erro abaixo do input de arquivo.
     * @param {HTMLInputElement} input O input que gerou o erro.
     * @param {string} message A mensagem de erro.
     */
    function showUploadError(input, message) {
        clearUploadError(input); // Remove erro anterior se houver
        const errorDiv = document.createElement("div");
        errorDiv.className = "upload-error";
        errorDiv.textContent = message;
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
        input.classList.add("error"); // Adiciona classe de erro ao input
    }

    /**
     * Remove a mensagem de erro de upload associada a um input.
     * @param {HTMLInputElement} input O input.
     */
    function clearUploadError(input) {
        const errorContainer = input.parentNode.querySelector(".upload-error");
        if (errorContainer) {
            errorContainer.remove();
        }
        input.classList.remove("error"); // Remove classe de erro do input
    }

    /**
     * Cria o container para exibir mensagens de erro do formulário.
     * @param {HTMLFormElement} form O formulário onde o container será inserido.
     * @returns {HTMLElement} O elemento container de erros.
     */
    function createErrorContainer(form) {
        let container = document.getElementById("error-messages");
        if (!container && form) {
            container = document.createElement("div");
            container.id = "error-messages";
            container.className = "alert error"; // Usa a classe de alerta existente
            container.style.display = "none"; // Começa oculto
            form.prepend(container); // Adiciona no início do formulário
        }
        return container;
    }

    /**
     * Exibe as mensagens de erro no container e rola até ele.
     * @param {string[]} errorMessages Array de mensagens de erro.
     * @param {HTMLElement} errorContainer O container onde exibir os erros.
     * @param {HTMLFormElement} form O formulário para encontrar o primeiro campo com erro.
     */
    function displayErrors(errorMessages, errorContainer, form) {
        if (errorMessages.length > 0 && errorContainer) {
            errorContainer.innerHTML = `
                <strong>Por favor, corrija os seguintes erros:</strong>
                <ul>
                    ${errorMessages.map((msg) => `<li>${msg}</li>`).join("")}
                </ul>
            `;
            errorContainer.style.display = "block";
            errorContainer.scrollIntoView({ behavior: "smooth", block: "start" });

            // Foca no primeiro campo com erro, se possível
            const firstErrorField = form.querySelector(".error");
            if (firstErrorField) {
                firstErrorField.focus();
            }
        } else if (errorContainer) {
            errorContainer.style.display = "none";
        }
    }

} // Fim do if (document.querySelector(".historico-section"))
