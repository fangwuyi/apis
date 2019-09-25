//url获取参数
function getQueryVariable(variable)
{
   var query = window.location.search.substring(1);
   var vars = query.split("&");
   for (var i=0;i<vars.length;i++) {
       var pair = vars[i].split("=");
       if(pair[0] == variable){return pair[1];}
   }
   return(false);
}

var app_index = new Vue({
	el: '#app',
	data: {
		aid: 0,
		title: '昕薇',
		article: [],
	},
	methods: {//不希望缓存
		//加载页面数据
		loadData: function(){
			let that = this
			let aid = that.aid
	    	let ajaxUrl = '/index.php/api/index/detail'
			let ajaxData = {id:aid}
			if(!aid){
				alert('参数丢失')
				return false
			}
	        axios.post(ajaxUrl,ajaxData)
			.then(function (response) {
				let res = response.data
			    if( res.errcode===0 ){
			    	console.log('res:',res)
			    	let article = res.data ? res.data.news : null;
			    	if(article){
			    		that.title = article.title
			    		that.article = article
			    	}
			    	console.log('article:',)
			    }else{
			    	alert(res.errmsg)
			    }
			})
			.catch(function (error) {
			    console.log('error:',error);
			});
		}
	},
	watch: {
		"checkedNames": function() {
			if (this.checkedNames.length == this.checkedArr.length) {
				this.checked = true
			} else {
				this.checked = false
			}
		}
	},
	computed: {//希望缓存
	    // 计算属性的 getter
	    reversedMessage: function () {
	        // `this` 指向 app 实例
	        return this.keworys.split(',').reverse().join(',')
	    }
	},
	filters: {
    	capitalize: function (value) {
	    	if (!value) return ''
	        value = value.toString()
	    	// console.log(value.slice(1))
	        return value.charAt(0).toUpperCase() + value.slice(1)
	    }
	},
	created: function () {
		this.aid = getQueryVariable('id');
		//加载页面数据
		this.loadData();
	}
})