
class MyCrud {
    //Os campos adicionados são definidos pelo primeiro elemento na lista.
    //Os objetos adicionado na lista são definidos pelos inputs do form.
    constructor() {
        if (!MyCrud.instance) {
            this._table = localStorage.getItem("tableCrud");
            if (this._table == null) {
                this._table = [];
            }
            else {
                this._table = JSON.parse(this._table);
            }
            MyCrud.instance = this;
        }
        return MyCrud.instance;
    }

    static isEmpty(elm) {
        //Testa um input pra ver se está vazio
        if (elm.value == "") {
            return true;
        }
        return false;
    }

    create(jsn) {
        //Recebe um JSON e da push na _table
        this._table.push(jsn);
        localStorage.setItem("tableCrud", JSON.stringify(this._table));
    }

    read(start, max, field = null, search = "") {
        //Busca o cadastro selecionado e se encontrado retorna um 
        //map com a posição da lista sendo a chave e o _table[posicao] sendo o valor,
        //ex: { 1 : { foo : bar, ...}, 5 : { foo : ba, ...}, ...} 
        //se não encontrar nada retorna um objeto vazio
        //start: posiçao de começo da busca, max: numero maximo de registros buscados
        //search: string para busca específica, 
        //field: qual campo do objeto o 'search' deve ser checado
        let res = {}
        let nfound = 0;
        if ((this._table == null) || (this._table.length == 0)) { return res; }
        for (let i = start; (nfound < max) && (i < this._table.length); i++) {
            //Optei por não ser case sensitive
            if (field != null) {
                if (this._table[i][field].toLowerCase().match(search.toLowerCase())) {
                    res[i] = this._table[i];
                    //Contador só é incrementado quando um valor é encontrado
                    nfound++;
                }
            }
            else {
                res[i] = this._table[i];
                nfound++;
            }
        }
        return res;
    }

    update(i, map = {}) {
        for (let key in map) {
            this._table[i][key] = map[key];
        }
        localStorage.setItem("tableCrud", JSON.stringify(this._table));
    }

    delete(i) {
        if (i >= 0 && i < this._table.length) {
            this._table.splice(i, 1); 
            localStorage.setItem("tableCrud", JSON.stringify(this._table));
        }
    }

}


class View {
    //Classe responsável por usar o MyCrud e montar a interface
    constructor() {
        this.crud = new MyCrud();
    }

    create(form) {
        let reg = {};
        for (let inp of form) {
            if (inp.hasAttribute("required") && MyCrud.isEmpty(inp)) {
                return false;
            }
            if (!(inp.getAttribute("type") == "submit" || inp.getAttribute("type") == "reset")) {
                reg[inp.getAttribute("name")] = inp.value;
            }
        }
        if (!reg.nome || !reg.email) { // Adicionando validação para nome e email
            return false;
        }
        this.crud.create(reg);
        window.dispatchEvent(new Event("load"));
        form.reset();
        return true;
    }

    raiseMessage(tbl, type) {
        //Mensagens que vão aparecer no topo da tabela depois que alguma ação for feita
        let div = document.createElement("div");
        if (type === "empty") {
            div.textContent = "Nenhum valor encontrado";
            div.setAttribute("class", "alert alert-secondary");
            tbl.appendChild(div);
            return;
        }
        else if (type === "update-success") {
            div.textContent = "Registro editado!";
            div.setAttribute("class", "alert alert-success alert-dismissible fade show");
        }
        else if (type === "update-fail") {
            div.textContent = "Registro não pôde ser editado, verifique se os campos foram preenchidos corretamente.";
            div.setAttribute("class", "alert alert-danger alert-dismissible fade show");
        }
        else if (type === "create-success") {
            div.textContent = "Registro inserido com sucesso.";
            div.setAttribute("class", "alert alert-success alert-dismissible fade show");
        }
        else if (type === "create-fail") {
            div.textContent = "Registro não pôde ser inserido, verifique se os campos foram preenchidos corretamente.";
            div.setAttribute("class", "alert alert-danger alert-dismissible fade show");
        }
        else if (type === "delete") {
            div.textContent = "Registro removido!";
            div.setAttribute("class", "alert alert-warning alert-dismissible fade show");
        }
        div.setAttribute("role", "alert");
        let b = document.createElement("button");
        b.setAttribute("type", "button");
        b.setAttribute("class", "close");
        b.setAttribute("data-dismiss", "alert");
        let span = document.createElement("span");
        span.setAttribute("class", "fas fa-times");
        b.appendChild(span);
        div.appendChild(b);
        tbl.parentNode.insertBefore(div, tbl);
    }

    list(tbl, start = 0, field = null, search = "") {
        //Limpa entradas antigas, gera a lista conforme o search e field
        tbl.textContent = "";
        let lst = this.crud.read(start, Infinity, field, search);
        if (Object.keys(lst).length == 0) {
            let div = document.createElement("div");
            this.raiseMessage(tbl, "empty");
            return;
        }

        //Gera os <th> com base no primeiro elemento da lista
        let tr = document.createElement("tr");
        for (let key in lst[Object.keys(lst)[0]]) {
            let th = document.createElement("th");
            th.setAttribute("class", "text-capitalize");
            th.textContent = key;
            tr.appendChild(th);
        }
        let th = document.createElement("th");
        th.textContent = "";
        tr.appendChild(th);

        tbl.appendChild(tr);

        //Imprime os registros na <table>
        for (let i in lst) {
            tr = document.createElement("tr");
            for (let key in lst[i]) {
                let td = document.createElement("td");
                //Transforma o formato padrao de data para o formato local
                if (lst[i][key].match(/\d{4}-\d{2}-\d{2}/)) {
                    td.textContent = new Date(lst[i][key]).toLocaleDateString("pt-BR", { timeZone: "UTC" });
                }
                else {
                    td.textContent = lst[i][key];
                }
                tr.appendChild(td);
            }
            //Gera o botão de update
            let td = document.createElement("td");
            let btn = document.createElement("button");
            let icon = document.createElement("i");
            //Classes do Bootstrap
            btn.setAttribute("class", "btn btn-primary m-1 float-right remove-row");
            //Insere o value correspondente à posição na lista
            btn.setAttribute("value", i);
            //Classes do font awesome
            icon.setAttribute("class", "fas fa-eraser");
            btn.appendChild(icon);
            td.appendChild(btn);

            //Gera o botão de delete
            btn = document.createElement("button");
            icon = document.createElement("i");
            //Classes e afins do Bootstrap
            btn.setAttribute("class", "btn btn-primary m-1 float-right update-row");
            btn.setAttribute("data-toggle", "modal");
            btn.setAttribute("data-target", "#update-modal");
            //Insere o value correspondente à posição na lista
            btn.setAttribute("value", i);
            //Classes do font awesome
            icon.setAttribute("class", "fas fa-edit");
            btn.appendChild(icon);
            td.appendChild(btn);

            tr.appendChild(td);

            tbl.appendChild(tr);
        }
    }

    fillForm(form, i) {
        //Preenche formulario com as informações do item de posição i da lista
        form.reset();
        let reg = this.crud.read(i, 1);
        reg = reg[Object.keys(reg)[0]];
        //Por algum motivo o efeito de transição do modal ta bugando os inputs de type date
        //então tive que botar um delay antes de preencher
        setTimeout(function () {
            for (let inp of form) {
                if (!(inp.getAttribute("type") == "submit" || inp.getAttribute("type") == "reset")) {
                    inp.value = reg[inp.getAttribute("name")];
                }
            }
        }, 210);
    }

    update(form, i) {
        //Atualiza com os campos especificados retorna false se os
        //campos necessários não foram preenchidos, senão retorna true
        let reg = {};
        for (let inp of form) {
            //Teste de campos obrigatorios
            if (inp.hasAttribute("required") && MyCrud.isEmpty(inp)) { return false; }
            //Insere informações no objeto
            if (!(inp.getAttribute("type") == "submit" || inp.getAttribute("type") == "reset")) {
                reg[inp.getAttribute("name")] = inp.value;
            }
        }
        this.crud.update(i, reg);
        window.dispatchEvent(new Event("load"));
        form.reset();
        return true;
    }


    delete(btn) {
        //Usa o indice atribuido ao value do botão para deletar aquele objeto.
        this.crud.delete(btn.getAttribute("value"));
        window.dispatchEvent(new Event("load"));
    }
}

var view = new View();

//Eventos com as ações do objeto View

document.getElementById("c-submit").addEventListener("click", function () {
    let table = document.getElementById("table-list");
    if (view.create(this.form)) {
        view.raiseMessage(table, "create-success");
    }
    else {
        view.raiseMessage(table, "create-fail");
    };
}, false);

document.getElementById("u-submit").addEventListener("click", function () {
    let table = document.getElementById("table-list");
    if (view.update(this.form, this.getAttribute("value"))) {
        view.raiseMessage(table, "update-success");
    }
    else {
        view.raiseMessage(table, "update-fail");
    };
}, false);

window.addEventListener("load", function () {
    let table = document.getElementById("table-list");
    view.list(table);
    document.getElementById("r-search").value = "";
    for (let btn of table.querySelectorAll("button.update-row")) {
        btn.addEventListener("click", function () {
            document.getElementById("u-submit").setAttribute("value", this.getAttribute("value"));
            view.fillForm(document.getElementById("update"), this.value);
        }, false);
    }
    for (let btn of table.querySelectorAll("button.remove-row")) {
        btn.addEventListener("click", function () {
            view.delete(this);
            view.raiseMessage(document.getElementById("table-list"), "delete");
        }, false);
    }
}, false);

function search(event) {
    event.preventDefault();
    
    let table = document.getElementById("table-list");
    view.list(table, 0, document.getElementById("search-options").value, document.getElementById("r-search").value);
    for (let btn of table.querySelectorAll("button.update-row")) {
        btn.addEventListener("click", function () {
            document.getElementById("u-submit").setAttribute("value", this.getAttribute("value"));
            view.fillForm(document.getElementById("update"), this.value);
        }, false);
    }
    for (let btn of table.querySelectorAll("button.remove-row")) {
        btn.addEventListener("click", function () {
            view.delete(this);
            view.raiseMessage(document.getElementById("table-list"), "delete");
        }, false);
    }
}

document.getElementById("r-search").addEventListener("keyup", search, false);
document.getElementById("r-submit").addEventListener("click", search, false);

//Input masks
function telefonemask(e) {
    if (e.target.value.length <= 14) {
        //Fixo
        var x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,4})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
    } else {
        //Celular
        var x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
    }
}
document.getElementById('c-telefone').addEventListener('input', telefonemask);
document.getElementById('u-telefone').addEventListener('input', telefonemask);