(() => {
    const form = document.getElementById('formTambahBagian');
    if (!form) return;
    const modal = document.getElementById('modalTambahBagian');
    const $field = id => form.querySelector('#' + id);
    const labels = {manual:'Isi target langsung', sum:'Jumlah anak', ratio:'Persentase dua nilai', weighted_sum:'Gabungan berbobot', scale:'Nilai × pengali', offset_ratio:'Nilai dasar + persentase'};
    const units = ['', 'orang','dokumen','file','nilai','rupiah','persen','prodi','kegiatan','indeks','unit','layanan','jumlah'];
    let root;
    let sequence = 0;
    let outputs = [];
    let openNodes = new Set();
    const fresh = (kode='', nama='', target=null, unit='persen', type='manual', children=[], config={}) => ({key:'new-' + (++sequence), id:null, kode, nama, target, jenis_target:unit, calculation_type:type, calculation_config:config, children});
    const clone = value => JSON.parse(JSON.stringify(value));
    const number = value => value === null || value === '' ? null : Number(String(value).replace(',', '.'));
    const format = (value, unit) => value === null ? '—' : value.toLocaleString('id-ID',{maximumFractionDigits:2}) + (unit === 'persen' ? '%' : unit ? ' ' + unit : '');
    const el = (tag, attrs={}, text='') => {
        const element=document.createElement(tag);
        Object.entries(attrs).forEach(([key,value]) => { if (key==='class') element.className=value; else element.setAttribute(key,value); });
        if (text) element.textContent=text;
        return element;
    };
    const field = (caption, control) => {
        const label=el('label',{class:'d-block quick-field'});
        label.append(document.createTextNode(caption),control);
        return label;
    };
    const input = (value, onInput, attrs={}) => {
        const element=el('input',{class:'form-control',type:'text',...attrs});
        element.value=value ?? '';
        element.addEventListener('input',()=>{onInput(element.value); refresh();});
        return element;
    };
    const select = (options,value,onChange) => {
        const element=el('select',{class:'form-control'});
        options.forEach(([key,text])=>element.add(new Option(text,key)));
        element.value=value ?? '';
        element.addEventListener('change',()=>{onChange(element.value); refresh();});
        return element;
    };
    const unitSelect = node => select([...new Set([...units,node.jenis_target || ''])].map(unit=>[unit,unit||'Pilih satuan']),node.jenis_target,value=>node.jenis_target=value);
    function calculate(node) {
        const children=new Map(node.children.map(child=>[child.key,calculate(child)]));
        const config=node.calculation_config || {};
        const read=key=>{
            const result=children.get(key);
            if (!result || result.value===null) throw Error('Lengkapi nilai sumber.');
            return result.value;
        };
        const scalar=(raw,label)=>{
            const value=number(raw);
            if(value===null||!Number.isFinite(value)||value<0||value>1000000)throw Error('Isi '+label+' yang valid.');
            return value;
        };
        try {
            let value;
            switch(node.calculation_type) {
                case 'manual': value=number(node.target); break;
                case 'sum': {
                    const ids=config.child_ids || [...children.keys()];
                    if (!ids.length) throw Error('Tambahkan rincian.');
                    value=ids.reduce((sum,key)=>sum+read(key),0); break;
                }
                case 'weighted_sum': {
                    const weights=config.weights || {};
                    if(Object.values(weights).some(weight=>weight!==''&&weight!==null&&(!Number.isFinite(number(weight))||number(weight)<0||number(weight)>100)))throw Error('Isi bobot dengan angka 0 sampai 100.');
                    if (!children.size || ![...children.keys()].some(key=>number(weights[key])>0)) throw Error('Tambahkan rincian dan isi bobotnya.');
                    if ([...children.keys()].some(key=>weights[key]===undefined)) throw Error('Isi bobot setiap rincian.');
                    value=Object.entries(weights).reduce((sum,[key,weight])=>sum+(number(weight)?read(key)*number(weight)/100:0),0); break;
                }
                case 'scale': value=read(config.source_id)*scalar(config.factor ?? 1,'pengali'); break;
                case 'ratio': case 'offset_ratio': {
                    const denominator=read(config.denominator_id);
                    if (!denominator) throw Error('Pembagi nol; hasil belum dapat dihitung.');
                    value=read(config.numerator_id)/denominator*scalar(config.factor ?? 100,'pengali')+(node.calculation_type==='offset_ratio'?scalar(config.baseline ?? 0,'nilai dasar'):0); break;
                }
            }
            if (value!==null && !Number.isFinite(value)) throw Error('Periksa angka yang diisi.');
            return {value:value ?? null, error:value===null?'Isi target sumber.':null};
        } catch(error) { return {value:null,error:error.message}; }
    }
    function syncRoot() {
        if (!root) return;
        root.kode=$field('kode').value;
        root.nama=$field('sub_indikator').value;
        root.target=$field('target').value;
        root.jenis_target=$field('jenis_target').value;
    }
    function refresh() {
        if (!root) return;
        syncRoot();
        $field('manual_target_group').hidden=root.calculation_type!=='manual';
        $field('quick_root_formula_label').textContent='Cara menghitung induk: ' + labels[root.calculation_type];
        outputs.forEach(({node,output})=>{
            const result=calculate(node);
            output.textContent='Hasil: '+format(result.value,node.jenis_target)+(result.error?' · '+result.error:'');
        });
        const result=calculate(root);
        $field('calculation_preview').textContent='Target induk: '+format(result.value,root.jenis_target)+(result.error?' · '+result.error:'');
    }
    function changeMethod(node,type) {
        if (node.calculation_type===type) return;
        node.calculation_type=type;
        const keys=node.children.map(child=>child.key);
        node.calculation_config=type==='weighted_sum'?{weights:Object.fromEntries(keys.map(key=>[key,0]))}:type==='scale'?{source_id:keys[0]||null,factor:1}:['ratio','offset_ratio'].includes(type)?{numerator_id:keys[0]||null,denominator_id:keys[1]||null,factor:100,...(type==='offset_ratio'?{baseline:0}:{})}:{};
    }
    function addChild(node) {
        const existing=new Set(node.children.map(child=>child.kode)); let code=1;
        while(existing.has(String(code))) code++;
        const child=fresh(String(code),'',null,node.jenis_target||'orang');
        node.children.push(child);
        const config=node.calculation_config;
        if(node.calculation_type==='weighted_sum') { config.weights ||= {}; config.weights[child.key]=0; }
        if(['ratio','offset_ratio'].includes(node.calculation_type)) {
            if(!config.numerator_id) config.numerator_id=child.key;
            else if(!config.denominator_id) config.denominator_id=child.key;
        }
        if(node.calculation_type==='scale' && !config.source_id) config.source_id=child.key;
        if(node.calculation_type==='sum' && config.child_ids) config.child_ids.push(child.key);
        render();
    }
    function parameters(node,container) {
        const config=node.calculation_config;
        const options=[['','Pilih rincian'],...node.children.map(child=>[child.key,child.kode+' — '+(child.nama||'Rincian baru')])];
        const reference=(label,key)=>container.append(field(label,select(options,config[key],value=>config[key]=value||null)));
        if(['ratio','offset_ratio'].includes(node.calculation_type)) { reference('Pembilang','numerator_id'); reference('Pembagi','denominator_id'); }
        if(node.calculation_type==='scale') reference('Sumber nilai','source_id');
        if(['ratio','offset_ratio','scale'].includes(node.calculation_type)) container.append(field('Pengali',input(config.factor ?? (node.calculation_type==='scale'?1:100),value=>config.factor=value,{inputmode:'decimal'})));
        if(node.calculation_type==='offset_ratio') container.append(field('Nilai dasar',input(config.baseline ?? 0,value=>config.baseline=value,{inputmode:'decimal'})));
        if(node.calculation_type==='weighted_sum') container.append(el('small',{class:'text-muted'},'Isi bobot pada setiap rincian di bawah. Contoh: 40, 40, 20.'));
        if(node.calculation_type==='sum' && config.child_ids) {
            container.append(el('small',{class:'d-block text-muted'},'Rincian yang dijumlahkan:'));
            node.children.forEach(child=>{
                const checkbox=el('input',{type:'checkbox'}); checkbox.checked=config.child_ids.includes(child.key);
                checkbox.addEventListener('change',()=>{config.child_ids=node.children.filter(item=>item.key===child.key?checkbox.checked:config.child_ids.includes(item.key)).map(item=>item.key); refresh();});
                const label=el('label',{class:'d-block'}); label.append(checkbox,document.createTextNode(' '+child.kode+' — '+child.nama)); container.append(label);
            });
        }
    }
    function renderNode(node,parent,container,depth) {
        const card=el('section',{class:'quick-node', 'data-node-key':node.key});
        const title=el('strong',{class:'d-block mb-2'});
        const updateTitle=()=>title.textContent=node.kode+' — '+(node.nama||'Rincian baru');
        updateTitle();card.append(title);
        const details=el('details',{class:'mb-2 quick-node-formula'});
        details.open=openNodes.has(node.key)||!node.nama;
        details.addEventListener('toggle',()=>{if(details.isConnected){if(details.open)openNodes.add(node.key);else openNodes.delete(node.key);}});
        details.append(el('summary',{},'Ubah rincian / rumus'));
        const metadata=el('div',{class:'quick-node-heading'});
        const name=el('textarea',{class:'form-control',rows:'2','aria-label':'Nama rincian'});
        name.value=node.nama;
        name.addEventListener('input',()=>{node.nama=name.value;updateTitle();refresh();});
        metadata.append(field('Kode',input(node.kode,value=>{node.kode=value;updateTitle();},{'aria-label':'Kode rincian'})),field('Nama rincian',name));
        details.append(metadata);
        const values=el('div',{class:'quick-node-values'});
        if(node.calculation_type==='manual') {
            values.append(field('Target',input(node.target,value=>node.target=value,{inputmode:'decimal','data-target-key':node.key})),field('Satuan',unitSelect(node)));
        } else {
            const output=el('p',{class:'quick-result'});outputs.push({node,output});card.append(output);
            details.append(field('Satuan',unitSelect(node)));
        }
        if(parent.calculation_type==='weighted_sum') {
            parent.calculation_config.weights ||= {};
            values.append(field('Bobot (%)',input(parent.calculation_config.weights[node.key] ?? '',value=>parent.calculation_config.weights[node.key]=value,{inputmode:'decimal','data-weight-key':node.key})));
        }
        card.append(values);
        details.append(field('Cara menghitung',select(Object.entries(labels),node.calculation_type,type=>{changeMethod(node,type);render();})));
        parameters(node,details);
        if(depth<6) {const add=el('button',{type:'button',class:'btn btn-outline-primary btn-sm'},'+ Rincian di bawah ini');add.addEventListener('click',()=>addChild(node));details.append(add);}
        if(!node.id && !hasSaved(node)) {
            const remove=el('button',{type:'button',class:'btn btn-link text-danger btn-sm'},'Hapus baris');
            remove.addEventListener('click',()=>{parent.children=parent.children.filter(child=>child!==node); const config=parent.calculation_config; if(config.weights) delete config.weights[node.key]; if(config.child_ids) config.child_ids=config.child_ids.filter(key=>key!==node.key); ['numerator_id','denominator_id','source_id'].forEach(key=>{if(config[key]===node.key)config[key]=null;}); render();}); details.append(remove);
        }
        card.append(details);
        node.children.forEach(child=>renderNode(child,node,card,depth+1));
        container.append(card);
    }
    const hasSaved=node=>Boolean(node.id)||node.children.some(hasSaved);
    function render() {
        outputs=[];
        $field('calculation_type').value=root.calculation_type;
        const parametersBox=$field('quick_root_parameters'); parametersBox.replaceChildren(); parameters(root,parametersBox);
        const children=$field('quick_children');children.replaceChildren();
        root.children.forEach(child=>renderNode(child,root,children,1));
        $field('quick_pattern').disabled=false;
        $field('quick_pattern_hint').textContent=hasSaved(root)?'Pola melengkapi struktur yang ada. Target, satuan, dan rincian tersimpan tetap dipertahankan.':'Pilih pola, isi semua target sumber di bawah, lalu Simpan sekali.';
        if($field('quick_pattern').value==='ikk24') $field('quick_pattern_hint').textContent+=' Rumus Excel menambahkan nilai dasar 50.';
        refresh();
    }
    function mergePattern(existing, proposal) {
        const keys=new Map(); const links=[];
        const merge=(current, planned, isRoot=false)=>{
            const used=new Set();
            const result={...planned, ...(current?{
                key:current.key,id:current.id,kode:current.kode||planned.kode,nama:current.nama||planned.nama,
                target:current.target,jenis_target:isRoot&&!current.id?planned.jenis_target:(current.jenis_target||planned.jenis_target)
            }:{})};
            keys.set(planned.key,result.key);
            result.children=planned.children.map(child=>{
                const match=current?.children.find(item=>!used.has(item.key)&&String(item.kode).trim().toUpperCase()===String(child.kode).trim().toUpperCase());
                if(match)used.add(match.key);
                return merge(match,child);
            });
            if(current)result.children.push(...current.children.filter(child=>!used.has(child.key)&&hasSaved(child)));
            links.push({result,planned});return result;
        };
        const result=merge(existing,proposal,true);
        links.forEach(({result,planned})=>{
            const config=clone(planned.calculation_config);
            ['source_id','numerator_id','denominator_id'].forEach(field=>{if(config[field])config[field]=keys.get(config[field])||config[field];});
            if(config.weights) {
                config.weights=Object.fromEntries(Object.entries(config.weights).map(([key,weight])=>[keys.get(key)||key,weight]));
                result.children.forEach(child=>{if(config.weights[child.key]===undefined)config.weights[child.key]=0;});
            }
            if(config.child_ids)config.child_ids=config.child_ids.map(key=>keys.get(key)||key);
            if(result.calculation_type==='sum'&&result.children.length>planned.children.length)config.child_ids=planned.children.map(child=>keys.get(child.key)||child.key);
            result.calculation_config=config;
        });
        return result;
    }
    function applyPattern(pattern) {
        if(pattern==='custom') return;
        syncRoot();
        const originalUnit=root.jenis_target || 'orang';
        const manual=(code,name,unit='persen')=>fresh(code,name,null,unit);
        const scale=(code,name,factor,child)=>fresh(code,name,null,'persen','scale',[child],{source_id:child.key,factor});
        const ratio=(code,name,factor,a,b)=>fresh(code,name,null,'persen','ratio',[a,b],{numerator_id:a.key,denominator_id:b.key,factor});
        let code='',name=''; let children=[],type='sum',config={};
        if(pattern==='ikk4') {
            code='IKK 4';name='Tingkat Pemenuhan Akreditasi dan Sertifikasi';
            children=[scale('A','Bobot 60%',60,manual('1','Akreditasi Lembaga','dokumen')),ratio('B','Bobot 40%',40,manual('1','Jumlah Program Studi Terakreditasi Minimal Baik Sekali','prodi'),manual('2','Jumlah Seluruh Program Studi','prodi'))];
        } else if(pattern==='ikk14') {
            code='IKK 14';name='Persentase Kualitas dan Kuantitas Dosen';type='weighted_sum';
            children=[manual('1','Realisasi Rasio Dosen terhadap Taruna Diklat Pembentukan'),manual('2','Realisasi Persentase Dosen Berkualifikasi S3 atau Berprestasi'),manual('3','Realisasi Persentase Dosen dengan Pendidikan Linier')];
            config={weights:Object.fromEntries(children.map((child,index)=>[child.key,[40,40,20][index]]))};
        } else if(pattern==='ikk5') {
            code='IKK 5';name='Indeks Peningkatan SDM Transportasi';
            children=[scale('A','Bobot 40%',0.4,manual('1','Persentase Penyerapan Lulusan Diklat Pembentukan')),scale('B','Bobot 60%',0.6,manual('1','Persentase Lulusan SDM Transportasi Bersertifikat Kompetensi'))];
        } else if(pattern==='ikk16') {
            code='IKK 16';name='Persentase Kerja Sama di Bidang Pendidikan dan Pelatihan';
            children=[ratio('A','Bobot 60%',60,manual('1','Kerja sama yang ditindaklanjuti','dokumen'),manual('2','Seluruh kerja sama yang berlaku','dokumen')),ratio('B','Bobot 40%',40,manual('1','Kerja sama yang disusun','dokumen'),manual('2','Target kerja sama yang disusun','dokumen'))];
        } else if(pattern==='ikk24') {
            code='IKK 24';name='Persentase Pemenuhan Akuntabilitas di Lingkungan BPSDMP';type='offset_ratio';
            children=[manual('3','Jumlah Layanan yang Dilaksanakan','layanan'),manual('4','Target Layanan','layanan')];config={numerator_id:children[0].key,denominator_id:children[1].key,factor:50,baseline:50};
        } else if(pattern==='ratio') {
            type='ratio';children=[manual('1','Pembilang','orang'),manual('2','Pembagi','orang')];config={numerator_id:children[0].key,denominator_id:children[1].key,factor:100};
        } else {
            type=pattern;children=[1,2,3].map(index=>manual(String(index),'',pattern==='sum'?originalUnit:'persen'));
            if(type==='weighted_sum') config={weights:Object.fromEntries(children.map(child=>[child.key,0]))};
        }
        root=mergePattern(root,{...root,kode:root.kode||code,nama:root.nama||name,children,calculation_type:type,calculation_config:config,target:null,jenis_target:pattern==='sum'?originalUnit:'persen'});
        $field('kode').value=root.kode;$field('sub_indikator').value=root.nama;$field('target').value=root.target??'';$field('jenis_target').value=root.jenis_target;
        render();
    }
    $('#modalTambahBagian').on('show.bs.modal',event=>{
        form.reset();
        openNodes = new Set();
        const button=$(event.relatedTarget);const id=button.data('bs-id');
        const settings=id?window.kontrakCalculationForms?.[String(id)]:null;
        root=settings?.tree?clone(settings.tree):fresh('', '', null, 'orang');
        $field('id').value=id||'';$field('id_parent').value=button.data('bs-id_parent')||'';
        $field('indikator_induk').value=button.data('bs-indikator_induk')||'';
        $field('kode').value=root.kode||'';$field('sub_indikator').value=root.nama||'';$field('target').value=root.target??'';
        if(root.jenis_target && ![...$field('jenis_target').options].some(option=>option.value===root.jenis_target))$field('jenis_target').add(new Option(root.jenis_target,root.jenis_target));
        $field('jenis_target').value=root.jenis_target||'orang';
        $field('quick_root_formula').open=false;$field('quick_pattern').value='custom';$field('respon_error').textContent='';render();
    });
    $('#modalTambahBagian').on('shown.bs.modal',()=>modal.scrollTop=0);
    $field('quick_pattern').addEventListener('change',event=>applyPattern(event.target.value));
    $field('calculation_type').addEventListener('change',event=>{syncRoot();changeMethod(root,event.target.value);render();});
    $field('quick_add_child').addEventListener('click',()=>addChild(root));
    ['kode','sub_indikator','target','jenis_target'].forEach(id=>$field(id).addEventListener('input',refresh));
    $field('jenis_target').addEventListener('change',refresh);
    form.onsubmit=async event=>{
        event.preventDefault();syncRoot();const button=$field('tombol_kirim');button.disabled=true;$field('respon_error').textContent='';
        try {
            const response=await axios.post('/save-kontrak-tree',{id_parent:$field('id_parent').value,jenis:form.querySelector('[name="jenis"]').value,root});
            if(response.data.responCode!==1)throw Error('Data belum tersimpan.');
            location.reload();
        } catch(error) {
            $field('respon_error').textContent=error.response?.data?.errors?Object.values(error.response.data.errors).flat().join(' '):'Data belum tersimpan. Periksa isian dan koneksi, lalu coba lagi.';
            modal.scrollTop=0;
        } finally {button.disabled=false;}
    };
})();
