JSONEditor.defaults.themes.jreviews = JSONEditor.AbstractTheme.extend({
  getHeader: function(text) {
    var el = document.createElement('div');
    el.className = 'jrFormHeading';
    if(typeof text === "string") {
      var title = document.createElement('span');
      title.className = 'title';
      title.innerHTML = text
      el.appendChild(title);
    }
    else {
      text.className = 'title';
      el.appendChild(text);
    }
    return el;
  },
  getGridRow: function() {
    var el = document.createElement('div');
    el.className = 'jrGrid';
    return el;
  },
  getSelectInput: function(options) {
    var el = this._super(options);
    el.className += 'jrSelect';
    //el.style.width = 'auto';
    return el;
  },
  setGridColumnSize: function(el,size) {
    el.className = 'jrCol'+size;
  },
  afterInputReady: function(input) {
    if(input.controlgroup) return;
    input.controlgroup = this.closest(input,'.form-group');
    if(this.closest(input,'.compact')) {
      // input.controlgroup.style.marginBottom = 0;
    }
  },
  getTextareaInput: function() {
    var el = document.createElement('textarea');
    el.className = 'jrTextArea';
    return el;
  },
  getRangeInput: function(min, max, step) {
    // TODO: use better slider
    return this._super(min, max, step);
  },
  getFormInputField: function(type) {
    var el = this._super(type);
    if(type !== 'checkbox') {
      el.className += 'getFormInputField';
    }
    return el;
  },
  getFormControl: function(label, input, description) {
    var group = document.createElement('div');

    if(label && input.type === 'checkbox') {
      group.className += 'jrFieldOption';
      label.appendChild(input);
      group.style.marginTop = '0';
      group.appendChild(label);
      input.style.position = 'relative';
      input.style.cssFloat = 'left';
    }
    else {
      group.className += ' jrFieldDiv';
      if(label) {
        label.className += ' jrLabel';
        group.appendChild(label);
      }
      group.appendChild(input);
    }

    if(description) group.appendChild(description);

    return group;
  },
  getCheckboxLabel: function(text) {
    var el = this.getFormInputLabel(text);
    return el;
  },
  getMultiCheckboxHolder: function(controls,label,description) {
    var el = document.createElement('div');
    el.className = 'jrFieldDiv';

    if(label) {
      label.className = 'jrLabel';
      el.appendChild(label);
    }

    for(var i in controls) {
      if(!controls.hasOwnProperty(i)) continue;
      el.appendChild(controls[i]);
    }

    if(description) el.appendChild(description);

    return el;
  },
  getFormInputDescription: function(text) {
    var el = document.createElement('p');
    el.className = 'jrFormBuilderHelpBlock';
    el.innerHTML = text;
    return el;
  },
  getIndentedPanel: function() {
    var el = document.createElement('div');
    el.className = 'jrFormBuilderPanel';
    return el;
  },
  getHeaderButtonHolder: function() {
    var el = this.getButtonHolder();
    //el.style.marginLeft = '10px';
    return el;
  },
  getButtonHolder: function() {
    var el = document.createElement('div');
    el.className = 'jrButtonGroup';
    return el;
  },
  getButton: function(text, icon, title) {
    var el = this._super(text, icon, title);
    el.className += 'jrButton jrSmall';
    return el;
  },
  getTable: function() {
    var el = document.createElement('table');
    el.className = 'jrTableGrid';
    return el;
  },
  getTableRow: function() {
    return document.createElement('tr');
  },
  getTableHead: function() {
    return document.createElement('thead');
  },
  getTableBody: function() {
    return document.createElement('tbody');
  },
  getTableHeaderCell: function(text) {
    var el = document.createElement('th');
    el.textContent = text;
    return el;
  },
  getTableCell: function() {
    var el = document.createElement('td');
    return el;
  },
  addInputError: function(input, text) {
    input.style.borderColor = 'red';
    if(!input.errmsg) {
      var group = this.closest(input,'.jrFieldDiv');
      input.errmsg = document.createElement('div');
      input.errmsg.setAttribute('class','errmsg');
      input.errmsg.style = input.errmsg.style || {};
      input.errmsg.style.color = 'red';
      group.appendChild(input.errmsg);
    }
    else {
      input.errmsg.style.display = 'block';
    }

    input.errmsg.innerHTML = '';
    input.errmsg.appendChild(document.createTextNode(text));
  },
  removeInputError: function(input) {
    input.style.borderColor = '';
    if(input.errmsg) input.errmsg.style.display = 'none';
  },
  getTabHolder: function() {
    var el = document.createElement('div');
    el.innerHTML = "<div class='jrTabs ui-tabs'></div><div class='jrTabsPanel ui-tabs-panel'></div><div style='clear:both;'></div>";
    return el;
  },
  // getTab: function(text) {
  //   var el = document.createElement('a');
  //   el.className = 'list-group-item';
  //   el.setAttribute('href','#');
  //   el.appendChild(text);
  //   return el;
  // },
  markTabActive: function(tab) {
    tab.className = tab.className.replace(/\s*ui-widget-header/g,'')+' ui-state-active';
  },
  markTabInactive: function(tab) {
    tab.className = tab.className.replace(/\s*ui-state-active/g,'')+' ui-state-default';
  }
});